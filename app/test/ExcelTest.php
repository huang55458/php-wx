<?php /** @noinspection ForgottenDebugOutputInspection */

namespace app\test;

use Exception;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\NamedFormula;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\Wizard;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Accounting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Currency;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Number;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Percentage;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Scientific;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Time;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column;
use PhpOffice\PhpSpreadsheet\Worksheet\AutoFilter\Column\Rule;
use PhpOffice\PhpSpreadsheet\Worksheet\CellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\TestCase;
use RuntimeException;
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
        ->setFormatCode((string)new Number(3, Number::WITH_THOUSANDS_SEPARATOR));
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
                $count = count(array_filter($row, static function ($value) {
                    return $value !== null;
                }));
                if ($count === 0) { // Ignore empty rows
                    continue;
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
            $count = count(array_filter($row, static function ($value) {
                return $value !== null;
            }));
            if ($count === 0) { // Ignore empty rows
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
                ['Q1', 12, 15, 21],
                ['Q2', 56, 73, 86],
                ['Q3', 52, 61, 69],
                ['Q4', 30, 32, 0],
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

    /**
     * 自动筛选
     * 在 MS Excel 中，自动过滤还允许对行进行排序。PhpSpreadsheet不支持此功能
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/autofilters/
     * @return void
     */
    public function testAutoFilter(): void
    {
        $spreadsheet = new Spreadsheet();
        $arrayData = [
            ['姓名', '性别', '年龄', '手机号', '邮箱', '身份证号', '创建时间', '更新时间'],
            ['程怡轩', '男', '33', '17257567775', 'ibuzb0450@gmail.com', '840217184412308297', '2024-07-22 09:13:28', '2024-08-22 09:13:28'],
            ['史妍', '女', '36', '18264964523', 'ukmx358@gmail.com', '409997196512144730', '2024-04-22 09:13:28', '2024-07-22 09:13:28'],
            ['武姣涵', '女', '28', '15816887969', 'ukmx358@gmail.com', '840217184412308297', '2023-07-22 09:13:28', '2024-07-22 09:13:28'],
        ];
        $arrayData = array_map(static function ($value) {
            if ($value[0] === '姓名') {
                return $value;
            }
            $value[6] = SharedDate::PHPToExcel($value[6]);
            $value[7] = SharedDate::PHPToExcel($value[7]);
            return $value;
        }, $arrayData);
        $spreadsheet->getActiveSheet()->fromArray($arrayData);
        // 将整个工作表设置为自动筛选区域,将启用过滤，但实际上并不应用任何过滤器
        $spreadsheet->getActiveSheet()->setAutoFilter(
            $spreadsheet->getActiveSheet()->calculateWorksheetDimension()
        );
        // 获得一个自动过滤列对象
        $autoFilter = $spreadsheet->getActiveSheet()->getAutoFilter();
        $columnFilter = $autoFilter->getColumn('A');
        // 简单过滤器,简单过滤器始终是 EQUALS 的比较匹配，而多个标准过滤器始终被视为由 OR 条件连接
        {
            $columnFilter->setFilterType(Column::AUTOFILTER_FILTERTYPE_FILTER);
            $columnFilter->createRule()->setRule(Rule::AUTOFILTER_COLUMN_RULE_EQUAL, '程怡轩');
            $columnFilter->createRule()->setRule(Rule::AUTOFILTER_COLUMN_RULE_EQUAL, '史妍');
            // 选择空白单元格
            $columnFilter->createRule()->setRule(Rule::AUTOFILTER_COLUMN_RULE_EQUAL, '');
        }
        // 日期过滤器
        {
            $columnFilter = $autoFilter->getColumn('G');
            $columnFilter->setFilterType(Column::AUTOFILTER_FILTERTYPE_FILTER);
            $columnFilter->createRule()->setRule(
                Rule::AUTOFILTER_COLUMN_RULE_EQUAL,
                [
                    'year' => 2024,
                    'month' => 7
                ]
            )->setRuleType(Rule::AUTOFILTER_RULETYPE_DATEGROUP);
        }
        // 自定义过滤器
        {
            $columnFilter = $autoFilter->getColumn('E');
            $columnFilter->setFilterType(Column::AUTOFILTER_FILTERTYPE_CUSTOMFILTER);
            /*
             *  *通配符匹配任意数量的字符
             *  ? 使用通配符匹配单个字符
             *  U*相当于“以‘U’开头”；*U相当于“以‘U’结尾”；*U*相当于“包含‘U’”
             *  需要注意，PhpSpreadsheet 仅在相等/不相等测试中识别通配符。如果要明确匹配*或?，可以使用波浪符号 进行转义~，
             *  ?~**就可以明确匹配* 单元格值中的第二个字符，后面跟着任意数量的其他字符。唯一需要转义的其他字符是 本身~
             */
            $columnFilter->createRule()
                ->setRule(
                    Rule::AUTOFILTER_COLUMN_RULE_EQUAL,
                    '*@gmail.com'
                )->setRuleType(Rule::AUTOFILTER_RULETYPE_CUSTOMFILTER);
            $columnFilter->createRule()
                ->setRule(
                    Rule::AUTOFILTER_COLUMN_RULE_NOTEQUAL,
                    '*358*'
                )->setRuleType(Rule::AUTOFILTER_RULETYPE_CUSTOMFILTER);
            // 修改连接条件为AND
            $columnFilter->setJoin(Column::AUTOFILTER_COLUMN_JOIN_AND);
        }
        // 动态过滤器, 比如创建时间为今天
        {
            $columnFilter = $autoFilter->getColumn('H');
            $columnFilter->setFilterType(
                Column::AUTOFILTER_FILTERTYPE_DYNAMICFILTER
            );
            // 定义动态过滤器的规则时，不定义值,可以简单地将其设置为空字符串
            $columnFilter->createRule()->setRule(
                Rule::AUTOFILTER_COLUMN_RULE_EQUAL,
                '',
                Rule::AUTOFILTER_RULETYPE_DYNAMIC_TODAY
            )->setRuleType(Rule::AUTOFILTER_RULETYPE_DYNAMICFILTER);
        }
        // 前十项
        {
            $columnFilter = $autoFilter->getColumn('B');
            $columnFilter->setFilterType(
                Column::AUTOFILTER_FILTERTYPE_TOPTENFILTER
            );
            // 过滤列中前 5% 的值
            $columnFilter->createRule()->setRule(
                Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_PERCENT,
                5,
                Rule::AUTOFILTER_COLUMN_RULE_TOPTEN_TOP
            )->setRuleType(Rule::AUTOFILTER_RULETYPE_TOPTENFILTER);
        }
        // 将符合过滤条件的所有行设置为可见，同时隐藏自动过滤区域内的所有其他行, 也会在保存的时候应用
        $autoFilter->showHideRows();
        // 重新应用电子表格中的所有过滤器
        //        $spreadsheet->reevaluateAutoFilters(false);

        // 显示已过滤的行
        {
            foreach ($spreadsheet->getActiveSheet()->getRowIterator() as $row) {
                if ($spreadsheet->getActiveSheet()->getRowDimension($row->getRowIndex())->getVisible()) {
                    if ($row->getRowIndex() === 1) {
                        continue;
                    }
                    $this->assertEquals(
                        '程怡轩',
                        $spreadsheet->getActiveSheet()->getCell('A' . $row->getRowIndex())->getValue()
                    );
                }
            }
        }
        // 结束行是工作表上使用的最后一行
        $spreadsheet->getActiveSheet()->getStyle(
            'G1:H' . $spreadsheet->getActiveSheet()->getHighestDataRow()
        )->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);
        $spreadsheet->getActiveSheet()->getAutoFilter()->setRangeToMaxRow();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save(sys_get_temp_dir() . "/test.xlsx");
    }

    /**
     * 工作表
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/worksheets/
     * @return void
     */
    public function testWorksheet(): void
    {
        $spreadsheet = $this->spreadsheet;
        // 索引位置访问单个工作表
        $spreadsheet->getSheet(0);
        // 通过名称访问工作表
        $spreadsheet->getSheetByName('Worksheet');
        // 打开工作簿时处于活动状态的工作表
        $spreadsheet->getActiveSheet();
        // 更改当前活动的工作表
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->setActiveSheetIndexByName('Worksheet');

        // 添加
        {
            $myWorkSheet = new Worksheet($spreadsheet, 'Worksheet 2');// 如果没有指定索引位置作为第二个参数，那么新工作表将被添加到最后一个现有工作表之后
            $spreadsheet->addSheet($myWorkSheet, 0);
        }
        // 复制
        {
            $clonedWorksheet = clone $spreadsheet->getSheetByName('Worksheet 2');
            $clonedWorksheet->setTitle('Copy of Worksheet 2');
            $spreadsheet->addSheet($clonedWorksheet);

            //复制样式 这个测试未通过，修改标题hashcode()校验不通过，抛出异常；不改标题直接提示已存在
            //            $clonedWorksheet2 = clone $spreadsheet->getSheetByName('Worksheet');
            //            $spreadsheet->addExternalSheet($clonedWorksheet2);
        }
        // 删除  删除当前活动的工作表，则前一个索引位置处的工作表将成为当前活动的工作表
        {
            $sheetIndex = $spreadsheet->getIndex(
                $spreadsheet->getSheetByName('Copy of Worksheet 2')
            );
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }
        $this->assertEquals(2, $spreadsheet->getSheetCount());
    }

    /**
     * 样式操作
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/
     * @return void
     * @throws Exception
     */
    public function testRecipes(): void
    {
        $spreadsheet = $this->spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        //设置电子表格的元数据
        {
            $spreadsheet->getProperties()
                ->setTitle("Test Document")
                ->setSubject("XLSX Test Document")
                ->setCreator("chumeng")
                ->setLastModifiedBy("chumeng")
                ->setCategory("Test result file")
                ->setKeywords("office 2007 php")
                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");
            $spreadsheet->getProperties()
                ->setCustomProperty('Editor', 'Mark Baker')
                ->setCustomProperty('Version', 1.17)
                ->setCustomProperty('Tested', true)
                ->setCustomProperty('Test Date', '2021-03-17', Properties::PROPERTY_TYPE_DATE);
        }
        //单元格公式
        {
            $worksheet->setCellValue('B8', '=IF(C4>500,"profit","loss")');
            $worksheet->setCellValue('D1', '=SUM(ABS(ANCHORARRAY(A1)))');
            // 以字符串的形式写入，不计算
            $worksheet->setCellValueExplicit('B8', '=IF(C4>500,"profit","loss")', DataType::TYPE_STRING);
        }
        //单元格中写入换行符“\n”（ALT+Enter）
        {
            $worksheet->getCell('A1')->setValue("hello\nworld");
            $worksheet->getStyle('A1')->getAlignment()->setWrapText(true);
            //自动为单元格启用“文本换行”
            Cell::setValueBinder(new AdvancedValueBinder());
            $worksheet->getCell('A1')->setValue("hello\nworld");
        }
        //明确设置单元格的数据类型 setCellValueExplicit() 也可以
        $worksheet->getCell('A1')->setValueExplicit('25', DataType::TYPE_NUMERIC);
        //将单元格更改为可点击的 URL
        {
            $worksheet->setCellValue('E26', 'www.phpexcel.net');
            $worksheet->getCell('E26')->getHyperlink()->setUrl('https://www.example.com');
            //指向另一个工作表/单元格的超链接
            $worksheet->setCellValue('E26', 'www.phpexcel.net');
            $worksheet->getCell('E26')->getHyperlink()->setUrl("sheet://'Sheetname'!A1");
        }
        // 打印机选项：略，暂时用不到，详细参考文档
        // 样式
        {
            //将单元格的前景色设置为红色、右对齐，并将边框设置为黑色和粗边框样式
            $worksheet->getStyle('B2')->getFont()->getColor()->setARGB(Color::COLOR_RED);
            $worksheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $worksheet->getStyle('B2')->getBorders()->getTop()->setBorderStyle(Border::BORDER_THICK);
            $worksheet->getStyle('B2')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK);
            $worksheet->getStyle('B2')->getBorders()->getLeft()->setBorderStyle(Border::BORDER_THICK);
            $worksheet->getStyle('B2')->getBorders()->getRight()->setBorderStyle(Border::BORDER_THICK);
            $worksheet->getStyle('B2')->getFill()->setFillType(Fill::FILL_SOLID);
            $worksheet->getStyle('B2')->getFill()->getStartColor()->setARGB('FFFF0000');
            //设置红色背景颜色
            $worksheet->getStyle('B3:B7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
            //将单元格的样式设置为字体加粗、右对齐、上边框细和渐变填充
            $styleArray = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'rotation' => 90,
                    'startColor' => [
                        'argb' => 'FFA0A0A0',
                    ],
                    'endColor' => [
                        'argb' => 'FFFFFFFF',
                    ],
                ],
            ];
            $worksheet->getStyle('A3')->applyFromArray($styleArray);
            //将 Style 导出为数组
            $worksheet->getStyle('A3')->exportArray();
        }
        //数字格式
        {
            $worksheet->getStyle('A1')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
            //前导零填充数字以达到固定长度时
            $worksheet->getCell('A1')->setValue(19);
            $worksheet->getStyle('A1')->getNumberFormat()->setFormatCode('0000'); // 0019
        }
        //垂直对齐设置为顶部
        $worksheet->getStyle('A1:D4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        //设置工作簿的默认样式
        {
            $spreadsheet->getDefaultStyle()->getFont()->setName('Arial');
            $spreadsheet->getDefaultStyle()->getFont()->setSize(8);
            //主题字体
            $spreadsheet->getTheme()->setThemeFontName('custom')
                ->setMinorFontValues('Arial', 'Arial', 'Arial', []);
            $spreadsheet->getDefaultStyle()->getFont()->setScheme('minor');
            //将绑定到主题字体的单元格更改为其他字体
            $spreadsheet->resetThemeFonts();
        }
        //单元格边框样式
        {
            $styleArray = [
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THICK,
                        'color' => ['argb' => 'FFFF0000'],
                    ],
                ],
            ];
            $worksheet->getStyle('B2:G8')->applyFromArray($styleArray);
            /*
             * $advancedBorders可以向 applyFromArray 提供第二个参数。默认值为true
             * 设置为此值时，边框样式将应用于整个范围，而不是单个单元格。设置为 时false，边框样式将应用于每个单元格
             */
            $worksheet->setShowGridlines(false);
            $styleArray = [
                'borders' => [
                    'bottom' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FFFF0000']],
                    'top' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FFFF0000']],
                    'right' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FF00FF00']],
                    'left' => ['borderStyle' => 'hair', 'color' => ['argb' => 'FF00FF00']],
                ],
            ];
            $worksheet->getStyle('B2:C3')->applyFromArray($styleArray);
            $worksheet->getStyle('B5:C6')->applyFromArray($styleArray, false);
        }
        //单元格的条件格式
        {
            //单元格的值小于零，则可以将其前景色设置为红色；如果单元格的值大于或等于零，则可以将其前景色设置为绿色
            $conditional1 = new Conditional();
            $conditional1->setConditionType(Conditional::CONDITION_CELLIS);
            $conditional1->setOperatorType(Conditional::OPERATOR_LESSTHAN);
            $conditional1->addCondition('0');
            $conditional1->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_RED);
            $conditional1->getStyle()->getFont()->setBold(true);

            $conditional2 = new Conditional();
            $conditional2->setConditionType(Conditional::CONDITION_CELLIS);
            $conditional2->setOperatorType(Conditional::OPERATOR_GREATERTHANOREQUAL);
            $conditional2->addCondition('0');
            $conditional2->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_GREEN);
            $conditional2->getStyle()->getFont()->setBold(true);

            $conditionalStyles = $spreadsheet->getActiveSheet()->getStyle('B2')->getConditionalStyles();
            $conditionalStyles[] = $conditional1;
            $conditionalStyles[] = $conditional2;

            $spreadsheet->getActiveSheet()->getStyle('B2')->setConditionalStyles($conditionalStyles);
            //将规则集复制到其他单元格
            $worksheet->duplicateStyle($spreadsheet->getActiveSheet()->getStyle('B2'), 'B3:B7');
        }
        //单元格添加注释
        {
            $worksheet->getComment('E11')->setAuthor('chumeng');
            $commentRichText = $worksheet->getComment('E11')->getText()->createTextRun('PhpSpreadsheet:');
            $commentRichText->getFont()->setBold(true);
            $worksheet->getComment('E11')->getText()->createTextRun("\r\n");
            $worksheet->getComment('E11')->getText()->createTextRun('Total amount on the current invoice, excluding VAT.');
            //添加带有背景图像的注释
            $worksheet->setCellValue('B5', 'picture');
            $drawing = new Drawing();
            $drawing->setName('comment');
            $drawing->setPath('C:\Users\Administrator\Pictures\1.png');
            $comment = $worksheet->getComment('B5');
            $comment->setBackgroundImage($drawing);
            $comment->setSizeAsBackgroundImage();
        }
        //在电子表格上设置安全性
        {
            /*
             * “保护”与“加密”不同。保护是为了防止电子表格的某些部分被更改，而不是为了防止电子表格被查看
             * PhpSpreadsheet不支持加密电子表格；也不能读取加密的电子表格
             * Excel 提供三个级别的“保护”
             */
            //启用工作表保护
            $spreadsheet->getActiveSheet()->getProtection()->setSheet(true);
            // Document：允许在完整的电子表格上设置密码，只有输入该密码才能进行更改
            $security = $spreadsheet->getSecurity();
            $security->setLockWindows(true);
            $security->setLockStructure(true);
            $security->setWorkbookPassword("PhpSpreadsheet");
            // Worksheet:提供其他安全选项,用户可以排序，插入行或格式化单元格而无需取消保护
            $protection = $spreadsheet->getActiveSheet()->getProtection();
            $protection->setPassword('PhpSpreadsheet');
            $protection->setSheet(true);
            $protection->setSort(false);
            $protection->setInsertRows(false);
            $protection->setFormatCells(false);
            //如果允许排序而不提供工作表密码，则需要明确启用允许排序的单元格范围，无论是否使用范围密码
            $worksheet->protectCells('A:A');
            $worksheet->protectCells('B:B', 'sortPW');
            // Cell:提供锁定/解锁单元格以及显示/隐藏内部公式的选项
            $spreadsheet->getActiveSheet()->getStyle('B1')->getProtection()
                ->setLocked(Protection::PROTECTION_UNPROTECTED)
                ->setHidden(Protection::PROTECTION_PROTECTED);
        }
        // 读取受保护的电子表格
        {
            $protection = $spreadsheet->getActiveSheet()->getProtection();
            $allowed = $protection->verify('PhpSpreadsheet');

            if ($allowed) {
                $spreadsheet->getActiveSheet();
            } else {
                throw new RuntimeException('Incorrect password');
            }
        }
        //在单元格上设置数据验证
        {
            //仅允许在单元格 B3 中输入 10 到 20 之间的数字
            $validation = $spreadsheet->getActiveSheet()->getCell('B3')->getDataValidation();
            $validation->setType(DataValidation::TYPE_WHOLE);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Number is not allowed!');
            $validation->setPromptTitle('Allowed input');
            $validation->setPrompt('Only numbers between 10 and 20 are allowed.');
            $validation->setFormula1(10);
            $validation->setFormula2(20);

            //只允许将从数据列表中挑选的项目输入到单元格 B5 中
            $validation = $spreadsheet->getActiveSheet()->getCell('B5')->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(false);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Input error');
            $validation->setError('Value is not in list.');
            $validation->setPromptTitle('Pick from list');
            $validation->setPrompt('Please pick a value from the drop-down list.');
            $validation->setFormula1('"Item A,Item B,Item C"');

            //需要对多个单元格进行数据验证 两种方式
            $spreadsheet->getActiveSheet()->getCell('B8')->setDataValidation(clone $validation);
            $validation->setSqref('B5:B1048576');
        }
        //设置列宽
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(120, 'pt'); //可以指定单位
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        //设置行高
        $spreadsheet->getActiveSheet()->getRowDimension('10')->setRowHeight(100);
        $spreadsheet->getActiveSheet()->getRowDimension('10')->setRowHeight(100, 'pt');
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(//14.5为字体Calibri 11的高度
            14.5 * (substr_count($worksheet->getCell('A1')->getValue(), "\n") + 1)
        );
        //显示、隐藏列  显示 C 列，隐藏 D 列
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setVisible(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setVisible(false);
        // 显示/隐藏行
        $spreadsheet->getActiveSheet()->getRowDimension('10')->setVisible(false);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setOutlineLevel(1);
        // Group/outline
        {
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setCollapsed(true);
            $spreadsheet->getActiveSheet()->getColumnDimension('E')->setVisible(false);
            //摘要添加到左侧
            $spreadsheet->getActiveSheet()->setShowSummaryRight(false);

            // 折叠行
            $spreadsheet->getActiveSheet()->getRowDimension('5')->setOutlineLevel(1);
            $spreadsheet->getActiveSheet()->getRowDimension('5')->setCollapsed(true);
            $spreadsheet->getActiveSheet()->getRowDimension('5')->setVisible(false);
            //将摘要添加到上方
            $spreadsheet->getActiveSheet()->setShowSummaryBelow(false);
        }
        //合并/取消合并单元格
        $spreadsheet->getActiveSheet()->mergeCells('A18:E22', Worksheet::MERGE_CELL_CONTENT_MERGE);
        $spreadsheet->getActiveSheet()->unmergeCells('A18:E22');
        // 第 7 行之前插入 2 个新行
        $spreadsheet->getActiveSheet()->insertNewRowBefore(7, 2);
        //第 7 行开始删除 2 行（即第 7 行和第 8 行）
        $spreadsheet->getActiveSheet()->removeRow(7, 2);
        $spreadsheet->getActiveSheet()->removeColumn('C', 2);
        //向单元格添加富文本
        {
            $richText = new RichText();
            $richText->createText('This invoice is ');
            $payable = $richText->createTextRun('payable within thirty days after the end of the month');
            $payable->getFont()->setBold(true);
            $payable->getFont()->setItalic(true);
            $payable->getFont()->setColor(new Color(Color::COLOR_DARKGREEN));
            $richText->createText(', unless specified otherwise on the invoice.');
            $spreadsheet->getActiveSheet()->getCell('A18')->setValue($richText);
        }
        //定义命名范围
        $spreadsheet->addNamedRange(new NamedRange('PersonFN', $spreadsheet->getActiveSheet(), '$B$1'));
        $spreadsheet->addNamedRange(new NamedRange('PersonLN', $spreadsheet->getActiveSheet(), '$B$2'));
        //定义命名公式
        $spreadsheet->addNamedFormula(new NamedFormula('GERMAN_VAT_RATE', $worksheet, '=16.0%'));
        $spreadsheet->addNamedFormula(new NamedFormula('CALCULATED_PRICE', $worksheet, '=$B1*$C1'));

        /*
         * 将输出重定向到客户端的 Web 浏览器:
         * 1. 创建 PhpSpreadsheet
         * 2. 电子表格输出您想要输出的文档类型的 HTTP
         * 3. 标头使用\PhpOffice\PhpSpreadsheet\Writer\*您选择的，并保存到'php://output'
         *
         * \PhpOffice\PhpSpreadsheet\Writer\Xlsx写入时使用临时存储php://output。默认情况下，临时文件存储在脚本的工作目录中
         * 当没有访问权限时，它会返回到操作系统的临时文件位置。未经授权的查看可能不安全！根据操作系统的配置，任何使用同一临时存储文件夹的人
         * 都可以读取临时存储。当需要对文档保密时，建议不要使用php://output
         */
        {
            // excel 2007
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="file.xlsx"');
            header('Cache-Control: max-age=0');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');

            // csv
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="file.xls"');
            header('Cache-Control: max-age=0');
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }
        //设置默认列宽
        $spreadsheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(12);
        //设置默认行高
        $spreadsheet->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);
        //设置工作表缩放级别,缩放级别应在 10 - 400
        $spreadsheet->getActiveSheet()->getSheetView()->setZoomScale(75);
        //工作表标签颜色
        $worksheet->getTabColor()->setRGB('FF0000');
        //创建工作表
        $worksheet1 = $spreadsheet->createSheet();
        $worksheet1->setTitle('Another sheet');
        //隐藏工作表
        $spreadsheet->getActiveSheet()->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);
        //从右到左设置列
        $spreadsheet->getActiveSheet()->setRightToLeft(true);
        //GD 绘图,略
        //添加绘图
        {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath('C:\Users\Administrator\Pictures\1.png');
            $drawing->setHeight(36);
            $drawing->setCoordinates('B15');
            $drawing->setOffsetX(110);
            $drawing->setRotation(25);
            $drawing->getShadow()->setVisible(true);
            $drawing->getShadow()->setDirection(45);

            $drawing->setWorksheet($spreadsheet->getActiveSheet());
        }
    }
    /**
     * conditional-formatting:允许根据单元格的值设置格式选项
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/conditional-formatting/
     * @return void
     */
    public function testCondition(): void
    {
        $spreadsheet = $this->spreadsheet;

        {
            $conditional = new Conditional();
            $conditional->setConditionType(Conditional::CONDITION_CELLIS);
            $conditional->setOperatorType(Conditional::OPERATOR_GREATERTHAN);
            $conditional->addCondition(80);
            $conditional->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
            $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $conditional->getStyle()->getFill()->getStartColor()->setARGB(Color::COLOR_GREEN);
            $conditionalStyles = $spreadsheet->getActiveSheet()->getStyle('A1:A10')->getConditionalStyles();
            $conditionalStyles[] = $conditional;
            $spreadsheet->getActiveSheet()->getStyle('A1:A10')->setConditionalStyles($conditionalStyles);

            // 使用 Wizard
            $wizardFactory = new Wizard('A1:A10');
            $wizard = $wizardFactory->newRule(Wizard::CELL_VALUE);
            $wizard->greaterThan(80);
            $wizard->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
            $wizard->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $wizard->getStyle()->getFill()->getStartColor()->setARGB(Color::COLOR_GREEN);
            $wizard->getConditional();
        }
        {
            $conditional2 = new Conditional();
            $conditional2->setConditionType(Conditional::CONDITION_CELLIS);
            $conditional2->setOperatorType(Conditional::OPERATOR_LESSTHAN);
            $conditional2->addCondition(10);
            $conditional2->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_DARKRED);
            $conditional2->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $conditional2->getStyle()->getFill()->getStartColor()->setARGB(Color::COLOR_RED);

            $conditionalStyles = $spreadsheet->getActiveSheet()->getStyle('A1:A10')->getConditionalStyles();
            $conditionalStyles[] = $conditional2;
            $spreadsheet->getActiveSheet()->getStyle('A1:A10')->setConditionalStyles($conditionalStyles);

            // 使用 Wizard
            $wizardFactory = new Wizard('A1:A10');
            $wizard = $wizardFactory->newRule(Wizard::CELL_VALUE);
            $wizard->lessThan(10);// 调用的是 __call()
            $wizard->getStyle()->getFont()->getColor()->setARGB(Color::COLOR_DARKGREEN);
            $wizard->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
            $wizard->getStyle()->getFill()->getStartColor()->setARGB(Color::COLOR_GREEN);
            $wizard->getConditional();
        }
        {
            $conditional = new Conditional();
            $conditional->setConditionType(Conditional::CONDITION_CONTAINSTEXT);
            $conditional->setOperatorType(Conditional::OPERATOR_CONTAINSTEXT);
            $conditional->setText('"LL"');
            $conditional->setConditions(['NOT(ISERROR(SEARCH("LL",A14)))']);

            // 使用 Wizard
            $cellRange = 'A14:B16';
            $wizardFactory = new Wizard($cellRange);
            /** @var Wizard\TextValue $textWizard */
            $textWizard = $wizardFactory->newRule(Wizard::TEXT_VALUE);
            $textWizard->contains('LL');
        }
        // 主要是使用Wizard对各种数据类型进行条件判断，不是很需要，详细查看文档
    }

    /**
     * 计算引擎
     * @link https://phpspreadsheet.readthedocs.io/en/latest/topics/calculation-engine/
     * @return void
     */
    public function testCalculationEngine(): void
    {
        // 略
    }
}
