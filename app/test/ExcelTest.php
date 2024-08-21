<?php /** @noinspection ForgottenDebugOutputInspection */

namespace app\test;

use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Accounting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Currency;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Number;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Percentage;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Scientific;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Time;
use PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;
use think\App;

/**
 * PHPSpreadsheet 对内存的要求非常高
 * @link https://phpspreadsheet.readthedocs.io/en/latest
 */
class ExcelTest extends TestCase
{
    private Spreadsheet $spreadsheet;
    private Worksheet $worksheet;

    public function __construct(string $name)
    {
        parent::__construct($name);
        (new App())->initialize();
        $this->spreadsheet = $spreadsheet = IOFactory::load(sys_get_temp_dir() . "/test.xlsx");
        $this->worksheet = $spreadsheet->getActiveSheet();
    }

    // 设置单元格的格式掩码
    public function testRegisterJob(): void
    {
        function getCellValue($worksheet, $coordinate): void
        {
            dump($worksheet->getCell($coordinate)->getValue());// 单元格的底层值（或公式）
            dump($worksheet->getCell($coordinate)->getCalculatedValue());// 查看评估该公式的结果
            dump($worksheet->getCell($coordinate)->getFormattedValue());// 查看 MS Excel 中显示的值
            dump($worksheet->getCell($coordinate)->getStyle()->getNumberFormat()->getFormatCode());// 读取单元格的格式掩码
            dump('--------------------------');
        }

        // 数值
        $this->worksheet->getCell('A1')->setValue(-12345.67890);
        $this->worksheet->getCell('A1')->getStyle()->getNumberFormat() // 保留三位小数，使用千位分隔符
            ->setFormatCode((string) new Number(3, Number::WITH_THOUSANDS_SEPARATOR));
        getCellValue($this->worksheet, 'A1');

        // 货币
        $this->worksheet->getCell('A2')->setValue(-12345.67890);
        $currencyMask = new Currency('￥', 2);
        $this->worksheet->getCell('A2')->getStyle()->getNumberFormat()->setFormatCode($currencyMask);
        getCellValue($this->worksheet, 'A2');

        // 会计
        $this->worksheet->getCell('A3')->setValue(-12345.67890);
        $accountingMask = new Accounting('￥', 2);
        $this->worksheet->getCell('A3')->getStyle()->getNumberFormat()->setFormatCode($accountingMask);
        getCellValue($this->worksheet, 'A3');

        // 日期
        $this->worksheet->getCell('A4')->setValue(-12345.67890);
        $dateMask = new Date(' ', Date::MONTH_NAME_SHORT, Date::WEEKDAY_NAME_LONG); // 不加参数默认格式 yyyy-mm-dd
        $this->worksheet->getCell('A4')->getStyle()->getNumberFormat()->setFormatCode($dateMask);
        getCellValue($this->worksheet, 'A4');

        // 时间
        $this->worksheet->getCell('A5')->setValue(-12345.67890);
        $timeMask = new Time();
        $this->worksheet->getCell('A5')->getStyle()->getNumberFormat()->setFormatCode($timeMask);
        getCellValue($this->worksheet, 'A5');

        // 百分比
        $this->worksheet->getCell('A6')->setValue(-12345.67890);
        $percentageMask = new Percentage(locale: 'tr_TR');
        $this->worksheet->getCell('A6')->getStyle()->getNumberFormat()->setFormatCode($percentageMask);
        getCellValue($this->worksheet, 'A6');

        // 科学计数
        $this->worksheet->getCell('A6')->setValue(-12345.67890);
        $scientificMask = new Scientific(4, locale: 'nl_NL');
        $this->worksheet->getCell('A6')->getStyle()->getNumberFormat()->setFormatCode($scientificMask);
        getCellValue($this->worksheet, 'A6');

        // 自定义
        $this->worksheet->getCell('A7')->setValue(-12345.67890);
        $currencyMask = new Currency(
            '€',
            2,
            Number::WITH_THOUSANDS_SEPARATOR,
            Currency::TRAILING_SYMBOL,
            Currency::SYMBOL_WITH_SPACING
        );
        $compositeCurrencyMask = [
            '[Green]' . $currencyMask,
            '[Red]' . $currencyMask,
            $currencyMask,
        ];
        $this->worksheet->getCell('A7')
            ->getStyle()->getNumberFormat()
            ->setFormatCode(implode(';', $compositeCurrencyMask));
        getCellValue($this->worksheet, 'A7');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
        $writer->save(sys_get_temp_dir() . "/test.xlsx");
    }

    /**
     * Reading and writing to file
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-and-writing-to-file/
     * @return void
     */
    public function testReadingAndWriting(): void
    {
        $file_name = sys_get_temp_dir() . "/test.xlsx";
        // 自动类型解析模式
        IOFactory::load($file_name);

        // 显式模式(比自动类型解析模式稍快)
        $reader = IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load($file_name);
        $writer = IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save($file_name);

        // Xlsx
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);//仅读取数据
        $reader->setLoadSheetsOnly(["Sheet1", "My special sheet"]); // 仅阅读特定工作表
        //        $reader->setReadFilter( new MyReadFilter() ); // 仅读取特定单元格
        //        $reader->setIncludeCharts(true);
        $spreadsheet = $reader->load($file_name, $reader::LOAD_WITH_CHARTS | $reader::SKIP_EMPTY_CELLS);
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(false); // 禁用公式预先计算
        $writer->setOffice2003Compatibility(true); // 开启Office2003兼容性,应仅在需要时使用
        $writer->save($file_name);


        // Csv
        $csv = sys_get_temp_dir() . "/test.csv";
        Csv::setConstructorCallback(static function (Csv $reader) use ($csv) {
            $encoding = Csv::guessEncoding($csv, 'ISO-8859-2'); // default CP1252
            $reader->setInputEncoding($encoding);
            $reader->setDelimiter(';');
            $reader->setEnclosure('');
            $reader->setPreserveNullString(true); // 加载空字符串
            $reader->setSheetIndex(0); // 指定从 CSV
            $reader->setTestAutoDetect(false); // 抑制对 Mac 行结尾的测试
            $reader->setEscapeCharacter('');
        });
        $reader = new Csv();
        $spreadsheet = $reader->load($csv);
        $spreadsheet->getActiveSheet()->getCell('A1')->setValue(-12345.67890);
        //        $reader->loadIntoExisting($csv, $spreadsheet); // 导入到其他工作表
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setEnclosure();
        $writer->setPreCalculateFormulas(false); // 禁用公式预先计算
        $writer->setUseBOM(true); // 写入 UTF-8 CSV 文件
        $writer->setOutputEncoding('SJIS-WIN');
        $writer->setSheetIndex(0); // 指定将哪个工作表写入 CSV
        $writer->setEnclosureRequired(false); // 仅在需要时使用括号字符
        $writer->setLineEnding("\r\n");
        $writer->setVariableColumns(true); // 编写具有不同列数的 CSV 文件
        $writer->setSheetIndex(0);
        // 小数和千位分隔符
        StringHelper::setDecimalSeparator('.');
        StringHelper::setThousandsSeparator(',');
        $writer->save($csv);

        $this->assertEquals("\u{FEFF}-12345.6789\r\n", file_get_contents($csv));
        // Xls,Slk,Ods,Html,Pdf ...
    }

    /**
     * 数据迭代
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/Looping%20the%20Loop/
     * @see https://go.microsoft.com/fwlink/?LinkID=521962 示例文件
     * @return void
     */
    public function testLoop(): void
    {
        /**
         * toArray
         * 不仅增加了内存开销，尤其是在工作表较大的情况下；而且它还缺乏灵活性。它对如何在数组中返回每个单元格的数据提供了有限地控制。
         * 它可以返回原始单元格值（如果单元格包含公式，或者该值应解释为日期，则这不是特别有用）；
         * 它可以强制 PhpSpreadsheet 计算公式并返回计算值；或者它可以返回格式化的单元格值（包括计算任何公式），
         * 以便以人类可读的格式显示日期值，但也会在单元格样式应用的情况下使用千位分隔符格式化数值
         */
        $reader = IOFactory::createReader('Xlsx');
        $spreadsheet = $reader->load('E:\360Downloads\Financial Sample.xlsx');
        $worksheet = $spreadsheet->getActiveSheet();
        $start = memory_get_usage();
        $worksheet->toArray(returnCellRef: true);
        dump($first_memory_usage = memory_get_usage() - $start);

        // 使用rangeToArray()
        $maxDataRow = $worksheet->getHighestDataRow();
        $maxDataColumn = $worksheet->getHighestDataColumn();
        $startRow = 1;
        $batchSize = 100;
        $start = memory_get_usage();
        while ($startRow <= $maxDataRow) {
            $endRow = min($startRow + $batchSize, $maxDataRow);
            $dataArray = $worksheet->rangeToArray("A$startRow:$maxDataColumn$endRow", returnCellRef: true);
            $startRow += $batchSize;

            foreach ($dataArray as $row) {
                if (count(array_filter($row, static function ($value) {
                    return $value !== null;
                })) === 0) {
                    continue;   // Ignore empty rows
                }
                echo 1;
            }
        }
        dump($second_memory_usage = memory_get_usage() - $start);
        $this->assertGreaterThan($second_memory_usage, $first_memory_usage);


        // 使用rangeToArrayYieldRows(), yield 返回每一行
        $start = memory_get_usage();
        $rowGenerator = $worksheet->rangeToArrayYieldRows('A1:' . $maxDataColumn . $maxDataRow, returnCellRef: true);
        foreach ($rowGenerator as $row) {
            if (count(array_filter($row, static function ($value) {
                return $value !== null;
            })) === 0) {
                continue;
            }
            echo 1;
        }
        dump(memory_get_usage() - $start);

        /*
         * 使用迭代器比任何“数组”方法都快得多，而且没有内存开销；速度和内存优势
         * 可以直接访问单元格本身。我们可以查看其值、样式；可以确定它是否是公式，或格式化为日期/时间，是否是合并范围的一部分：可以准确选择要用它做什么
         * 注：下面的测试内存占用挺大，原因未知
         */
        $start = memory_get_usage();
        $rowIterator = $worksheet->getRowIterator();
        foreach ($rowIterator as $row) {
            if ($row->isEmpty(CellIterator::TREAT_EMPTY_STRING_AS_EMPTY_CELL |
                CellIterator::TREAT_NULL_VALUE_AS_EMPTY_CELL)) {
                continue;
            }

            $columnIterator = $row->getCellIterator();
            $columnIterator->setIfNotExists(CellIterator::IF_NOT_EXISTS_RETURN_NULL);
            foreach ($columnIterator as $cell) {
                if ($cell !== null) {
                    echo $cell->getValue();
                }
            }
        }
        dump(memory_get_usage() - $start);
    }

    /**
     * 时间处理
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/The%20Dating%20Game/
     * @return void
     */
    public function testDate(): void
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        // 将 Unix 时间戳、PHP DateTime 对象或可识别格式的字符串转换为 Excel 序列化时间戳
        $today = SharedDate::PHPToExcel(\time());

        /*
         * 请注意，基准日期本身是第 1 天；因此严格来说，基准日期 0 是“1899-12-31”：Excel 将 0 到 1 之间的任何值视为纯粹的时间值，
         * 尝试使用日期格式掩码（如“yyyy-mm-dd”）显示 0 将显示无效日期（如“1900-01-00”）而不是“1899-12-31”，但当使用时间格式掩码
         * （如“hh:mm:ss”）时，它将显示为“00:00:00”（午夜）。小于 0 的值作为日期或时间无效，因此在 Excel 中，
         * 日期“数字格式掩码”单元格中的负值将显示为“############”
         */
        $data = [
            ['Formatted Date', 'Numeric Value'],
            ['=C2', 1],
            ['=C3', 2],
            ['=C4', $today],
        ];
        $worksheet->fromArray($data, null, 'B1');
        $worksheet->getStyle('B2:B4')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        $worksheet->getStyle('B1:C1')->getFont()->setBold(true);
        $worksheet->getColumnDimension('B')->setAutoSize(true);

        /*
         * 日期始终是值的整数部分（1、2、44943）：值的小数部分用于表示时间占一天的百分比。因此，值 0.5 表示中午 12:00；值 0.25 表示早上 06:00；值 0.75 表示晚上 18:00
         * 大于 1 的浮点值，如 44943.5 被视为日期时间值：2023 年 1 月 17 日 12:00（中午）
         */
        $data = [
            ['Formatted Time', 'Numeric Value'],
            ['=E2', 0],
            ['=E3', 0.25],
            ['=E4', 0.5],
            ['=E5', 0.75],
            ['=E6', $today + 0.5],
        ];
        $worksheet->fromArray($data, null, 'D1', true);
        $worksheet->getStyle('D2:D5')->getNumberFormat()->setFormatCode('hh:mm:ss');
        $worksheet->getStyle('D6')->getNumberFormat()->setFormatCode('yyyy-mm-dd hh:mm:ss');
        $worksheet->getStyle('D1:E1')->getFont()->setBold(true);
        $worksheet->getColumnDimension('D')->setAutoSize(true);

        $formatCode = $worksheet->getStyle('B2')->getNumberFormat()->getFormatCode();
        $this->assertTrue(SharedDate::isDateTimeFormatCode($formatCode));
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save(sys_get_temp_dir() . "/test1.xlsx");
    }

    /**
     * 访问单元格
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/accessing-cells/
     * @return void
     */
    public function testAccessCell(): void
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getActiveSheet()->setCellValue('A1', 'PhpSpreadsheet'); // string
        $spreadsheet->getActiveSheet()->setCellValue([1, 2], 12345.6789); // numeric
        $spreadsheet->getActiveSheet()->setCellValue(new CellAddress('A3'), true); // boolean
        $spreadsheet->getActiveSheet()->setCellValue( // formula
            'A4',
            '=IF(A3, CONCATENATE(A1, " ", A2), CONCATENATE(A2, " ", A1))'
        );
        // 忽略公式
        $spreadsheet->getActiveSheet()->getCell('A4')->getStyle()->setQuotePrefix(true);

        // 在单元格中设置日期和/时间值
        $excelDateValue = SharedDate::PHPToExcel(time());
        $spreadsheet->getActiveSheet()->setCellValue('A6', $excelDateValue);
        $spreadsheet->getActiveSheet()->getStyle('A6')->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_DATETIME);

        // 设置带前导零的数字
        {
            // 将数据类型明确设置为字符串
            $spreadsheet->getActiveSheet()->setCellValueExplicit('A7', "01513789642", DataType::TYPE_STRING);
            // 数字格式掩码
            $spreadsheet->getActiveSheet()->setCellValue('B7', 1513789642);
            $spreadsheet->getActiveSheet()->getStyle('B7')->getNumberFormat()->setFormatCode('00000000000');
        }

        // 从数组中设置单元格
        {
            $arrayData = [
                [null, 2010, 2011, 2012],
                ['Q1',   12,   15,   21],
                ['Q2',   56,   73,   86],
                ['Q3',   52,   61,   69],
                ['Q4',   30,   32,    0],
            ];
            $spreadsheet->getActiveSheet()->fromArray($arrayData, null, 'C3');
        }

        // 调用getCell()，并且该单元格尚不存在，则 PhpSpreadsheet 将创建该单元格
        $spreadsheet->getActiveSheet()->getCell('B8')->setValue('Some value');

        // 检索单元格值, 批量查看 testLoop()
        {
            // 原始、未格式化的值
            $spreadsheet->getActiveSheet()->getCell('A1')->getValue();
            // 检索计算值而不是公式本身
            $spreadsheet->getActiveSheet()->getCell('A4')->getCalculatedValue();
            // 应用了任何单元格格式的值
            $spreadsheet->getActiveSheet()->getCell('A6')->getFormattedValue();
        }

        // 会自动将百分比、科学格式的数字和以字符串形式输入的日期转换为正确的格式，同时设置单元格的样式信息
        Cell::setValueBinder(new AdvancedValueBinder());
        $spreadsheet->getActiveSheet()->setCellValue('B1', '10%');
        $spreadsheet->getActiveSheet()->setCellValue('B2', '21 December 1983');
        $this->assertEquals(
            '1983-12-21',
            $spreadsheet->getActiveSheet()->getCell('B2')->getFormattedValue()
        );
        dump($spreadsheet->getActiveSheet()->toArray(returnCellRef: true));
    }
}
