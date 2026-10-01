<?php

namespace Abs\RsaCasePkg;

use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use PHPExcel_Cell_DefaultValueBinder;
use PHPExcel_Shared_String;
use ZipArchive;

/**
 * Streams data rows into a worksheet of an xlsx file already saved by Maatwebsite Excel / PHPExcel.
 *
 * PHPExcel keeps every cell of a workbook in memory, which takes minutes and gigabytes for large exports.
 * Here the workbook (summary sheets, header row, styles) is still created by PHPExcel, and only the bulk
 * data rows are written to a temporary file one at a time and then spliced into the saved worksheet.
 * Cell values are typed exactly as LaravelExcelWorksheet::fromArray() with strict null comparison would type them.
 */
class StreamingXlsxSheetWriter {
	/**
	 * Directory for the temporary files
	 * @var string
	 */
	protected $temporaryDirectory;

	/**
	 * Temporary file holding the <row> elements
	 * @var resource
	 */
	protected $rowsFile;

	/**
	 * Path of the temporary rows file
	 * @var string
	 */
	protected $rowsFilePath;

	/**
	 * Row number of the next row
	 * @var int
	 */
	protected $currentRow;

	/**
	 * Column letters by column index
	 * @var array
	 */
	protected $columnLetters = [];

	/**
	 * @param int $startRow Row number of the first streamed row
	 * @param string $temporaryDirectory Writable directory for the temporary files. The system temp directory is not used
	 *                                   because it is not always writable by the web server user.
	 */
	public function __construct($startRow, $temporaryDirectory) {
		if (!is_dir($temporaryDirectory)) {
			@mkdir($temporaryDirectory, 0777, true);
		}
		if (!is_dir($temporaryDirectory) || !is_writable($temporaryDirectory)) {
			throw new \Exception('Temporary directory for the excel export is not writable: ' . $temporaryDirectory);
		}
		$this->temporaryDirectory = $temporaryDirectory;

		$this->rowsFilePath = $this->createTemporaryFile('xlsx_rows_');
		$this->rowsFile = fopen($this->rowsFilePath, 'w+b');
		if ($this->rowsFile === false) {
			@unlink($this->rowsFilePath);
			throw new \Exception('Unable to create a temporary file for the excel rows');
		}
		$this->currentRow = $startRow;
	}

	/**
	 * Remove the temporary rows file
	 */
	public function __destruct() {
		$this->cleanup();
	}

	/**
	 * Append a row of cell values. Null values are left as empty cells.
	 * @param array $values
	 * @return void
	 */
	public function addRow(array $values) {
		$rowXml = '<row r="' . $this->currentRow . '">';
		$columnIndex = 0;
		foreach ($values as $value) {
			if ($value !== null) {
				$rowXml .= $this->cellXml($this->columnLetter($columnIndex) . $this->currentRow, $value);
			}
			$columnIndex++;
		}
		$rowXml .= '</row>';

		$this->write($this->rowsFile, $rowXml);
		$this->currentRow++;
	}

	/**
	 * Splice the streamed rows into a worksheet of the saved xlsx file, after the rows it already has
	 * @param string $xlsxPath
	 * @param string $sheetEntryName e.g. xl/worksheets/sheet2.xml
	 * @return void
	 * @throws \Exception
	 */
	public function writeIntoWorkbook($xlsxPath, $sheetEntryName) {
		$zip = new ZipArchive();
		if ($zip->open($xlsxPath) !== true) {
			throw new \Exception('Unable to open the generated excel file');
		}

		$sheetXml = $zip->getFromName($sheetEntryName);
		if ($sheetXml === false) {
			$zip->close();
			throw new \Exception('Worksheet ' . $sheetEntryName . ' not found in the generated excel file');
		}

		// Split the worksheet around the end of its sheet data
		$sheetDataEndPosition = strpos($sheetXml, '</sheetData>');
		if ($sheetDataEndPosition === false) {
			$sheetXml = str_replace('<sheetData/>', '<sheetData></sheetData>', $sheetXml);
			$sheetDataEndPosition = strpos($sheetXml, '</sheetData>');
		}
		$sheetXmlStart = substr($sheetXml, 0, $sheetDataEndPosition);
		$sheetXmlEnd = substr($sheetXml, $sheetDataEndPosition);

		// Extend the used range to the last streamed row
		$lastRow = $this->currentRow - 1;
		$sheetXmlStart = preg_replace_callback('/<dimension ref="([A-Z]+[0-9]+):([A-Z]+)([0-9]+)"\/>/', function ($matches) use ($lastRow) {
			return '<dimension ref="' . $matches[1] . ':' . $matches[2] . max((int) $matches[3], $lastRow) . '"/>';
		}, $sheetXmlStart, 1);

		$sheetFilePath = $this->createTemporaryFile('xlsx_sheet_');
		try {
			$sheetFile = fopen($sheetFilePath, 'wb');
			if ($sheetFile === false) {
				throw new \Exception('Unable to create a temporary file for the excel sheet');
			}
			$this->write($sheetFile, $sheetXmlStart);
			rewind($this->rowsFile);
			$rowsSize = fstat($this->rowsFile)['size'];
			if (stream_copy_to_stream($this->rowsFile, $sheetFile) !== $rowsSize) {
				throw new \Exception('Unable to write the excel rows to the temporary sheet file');
			}
			$this->write($sheetFile, $sheetXmlEnd);
			fclose($sheetFile);

			$zip->deleteName($sheetEntryName);
			$zip->addFile($sheetFilePath, $sheetEntryName);
			if (!$zip->close()) {
				throw new \Exception('Unable to write the generated excel file');
			}
		} finally {
			@unlink($sheetFilePath);
			$this->cleanup();
		}
	}

	/**
	 * Close and remove the temporary rows file
	 * @return void
	 */
	public function cleanup() {
		if (is_resource($this->rowsFile)) {
			fclose($this->rowsFile);
		}
		if ($this->rowsFilePath && file_exists($this->rowsFilePath)) {
			@unlink($this->rowsFilePath);
		}
	}

	/**
	 * @param string $prefix
	 * @return string
	 * @throws \Exception
	 */
	protected function createTemporaryFile($prefix) {
		$path = @tempnam($this->temporaryDirectory, $prefix);
		// tempnam() silently falls back to the system temp directory, which is what has to be avoided here
		if ($path === false || dirname($path) !== realpath($this->temporaryDirectory)) {
			if ($path) {
				@unlink($path);
			}
			throw new \Exception('Unable to create a temporary file in ' . $this->temporaryDirectory);
		}
		return $path;
	}

	/**
	 * Cell XML matching what PHPExcel_Writer_Excel2007_Worksheet writes for the value
	 * @param string $coordinate
	 * @param mixed $value
	 * @return string
	 */
	protected function cellXml($coordinate, $value) {
		// LaravelExcelWorksheet::setValueOfCell() stores numeric strings explicitly as strings,
		// every other value goes through the default value binder
		if (is_string($value) && is_numeric($value)) {
			$dataType = PHPExcel_Cell_DataType::TYPE_STRING;
		} else {
			if (is_string($value)) {
				$value = PHPExcel_Shared_String::SanitizeUTF8($value);
			}
			$dataType = PHPExcel_Cell_DefaultValueBinder::dataTypeForValue($value);
		}

		switch ($dataType) {
			case PHPExcel_Cell_DataType::TYPE_NUMERIC:
				return '<c r="' . $coordinate . '"><v>' . str_replace(',', '.', (string) (float) $value) . '</v></c>';
			case PHPExcel_Cell_DataType::TYPE_BOOL:
				return '<c r="' . $coordinate . '" t="b"><v>' . ($value ? '1' : '0') . '</v></c>';
			case PHPExcel_Cell_DataType::TYPE_FORMULA:
				// Formulas are not pre-calculated (excel.export.calculate is false)
				return '<c r="' . $coordinate . '" t="str"><f>' . $this->escape(substr($value, 1)) . '</f><v>0</v></c>';
			case PHPExcel_Cell_DataType::TYPE_ERROR:
				return '<c r="' . $coordinate . '" t="e"><v>' . $this->escape($value) . '</v></c>';
		}

		$value = PHPExcel_Cell_DataType::checkString((string) $value);
		if ($value === '') {
			return '<c r="' . $coordinate . '"/>';
		}
		$text = PHPExcel_Shared_String::ControlCharacterPHP2OOXML($value);
		$space = $text !== trim($text) ? ' xml:space="preserve"' : '';
		return '<c r="' . $coordinate . '" t="inlineStr"><is><t' . $space . '>' . htmlspecialchars($text) . '</t></is></c>';
	}

	/**
	 * Write to a file and fail instead of producing a truncated excel file (e.g. when the disk is full)
	 * @param resource $file
	 * @param string $content
	 * @return void
	 * @throws \Exception
	 */
	protected function write($file, $content) {
		if (fwrite($file, $content) !== strlen($content)) {
			throw new \Exception('Unable to write the excel file to the temporary directory');
		}
	}

	/**
	 * @param string $value
	 * @return string
	 */
	protected function escape($value) {
		return htmlspecialchars($value);
	}

	/**
	 * @param int $columnIndex Zero based
	 * @return string
	 */
	protected function columnLetter($columnIndex) {
		if (!isset($this->columnLetters[$columnIndex])) {
			$this->columnLetters[$columnIndex] = PHPExcel_Cell::stringFromColumnIndex($columnIndex);
		}
		return $this->columnLetters[$columnIndex];
	}
}
