<?php
declare (strict_types = 1);

namespace app\service;

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use think\Collection;

class ExcelService extends \think\Service
{
    // 26列，一般够用
    private array $column = [1=>'A', 2=>'B', 3=>'C', 4=>'D',5 =>'E', 6=>'F', 7=>'G', 8=>'H', 9=>'I', 10=>'J', 11=>'K', 12=>'L', 13=>'M', 14=>'N', 15=>'O', 16=>'P', 17=>'Q', 18=>'R', 19=>'S', 20=>'T', 21=>'U', 22=>'V', 23=>'W', 24=>'X', 25=>'Y', 26=>'Z'];
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register()
    {
    	//
    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //
    }

    /**
     * @param array $header ['name','tel']
     * @param $data
     * [
     *      ['name' => 'test', 'tel' => 123],
     *      ['name' => 'test2', 'tel' => 234]
     * ]
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function exportXlsx(array $header, $data)
    {
        $helper = new Sample();
        if ($helper->isCli()) {
            $helper->log('This example should only be run from a Web Browser' . PHP_EOL);

            return;
        }
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('chumeng')
            ->setLastModifiedBy('chumeng')
            ->setTitle('Office 2007 XLSX Document')
            ->setSubject('Office 2007 XLSX Document')
            ->setDescription('sanzhi data')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('result file');

        if (!is_array($data)) {
            $this->setValue($spreadsheet, $header, $data);
        } else {
            $this->setArrayValue($spreadsheet, $header, $data);
        }


        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('main');

        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="01simple.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function loadXlsx($filename) {
        $res = [];
        $spreadsheet = IOFactory::load($filename);
//        $spreadsheet->setActiveSheetIndex(1);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        $title = array_shift($data);
        foreach ($data as $value) {
            $tmp = [];
            foreach ($value as $k => $v) {
                $tmp[$title[$k]] = $v;
            }
            $res[] = $tmp;
        }
        return $res;
    }

    public function setValue($spreadsheet,array $header,Collection $data)
    {
        $map = [];
        $i = 1;
        while (!empty($header)) {
            $tmp = array_shift($header);
            $map[$tmp] = $this->column[$i];
            $spreadsheet->getActiveSheet()->getColumnDimension($this->column[$i])->setAutoSize(true);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($this->column[$i] . "1", $tmp);
            $i++;
        }

        $y = 2;
        while (!$data->isEmpty()) {
            $row = $data->shift();
            foreach ($row as $k => $v) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue($map[$k] . $y, $v);
            }
            $y++;
        }
    }

    public function setArrayValue($spreadsheet,array $header,array $data)
    {
        $map = [];
        $i = 1;
        while (!empty($header)) {
            $tmp = array_shift($header);
            $map[$tmp] = $this->column[$i];
            $spreadsheet->getActiveSheet()->getColumnDimension($this->column[$i])->setAutoSize(true);
            $spreadsheet->setActiveSheetIndex(0)->setCellValue($this->column[$i] . "1", $tmp);
            $i++;
        }

        $y = 2;
        while (!empty($data)) {
            $row = array_shift($data);
            foreach ($row as $k => $v) {
                $spreadsheet->setActiveSheetIndex(0)->setCellValue($map[$k] . $y, $v);
            }
            $y++;
        }
    }

    /**
     * 一些常用方法
     * @return void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function test() {
        // 创建新的 Excel 实例
        $spreadsheet = new Spreadsheet();
        // 获取当前工作表
        $worksheet = $spreadsheet->getActiveSheet();
        // 设置可以添加换行(\n)
        $worksheet->getStyle('B1:C1')->getAlignment()->setWrapText(true);
        // 设置自动调整列宽度
        $worksheet->getColumnDimension('A')->setAutoSize(true);
        // 设置列宽
        $worksheet->getColumnDimension('B')->setWidth(20);
        // 字体大小
        $worksheet->getStyle('C')->getFont()->setSize(16);

        // 将B1单元格设置为粗体字
        $spreadsheet->getActiveSheet()->getStyle('B1')->getFont()->setBold(true);
        // 将A7至B7两单元格设置为粗体字，Arial字体，10号字
        $spreadsheet->getActiveSheet()->getStyle('A7:B7')->getFont()->setBold(true)->setName('Arial')->setSize(10);


        // 将文字颜色设置为红色。
        $spreadsheet->getActiveSheet()->getStyle('A4')->getFont()->getColor()->setARGB(Color::COLOR_RED);

        // 可以将图片加载到Excel中。
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo');
        $drawing->setPath('./images/officelogo.jpg');
        $drawing->setHeight(36);


        //设置第10行行高为100pt
        $spreadsheet->getActiveSheet()->getRowDimension(10)->setRowHeight(100);
        //设置默认行高
        $spreadsheet->getActiveSheet()->getDefaultRowDimension()->setRowHeight(15);

        // 将A1单元格设置为水平居中对齐
        $styleArray = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ];
        $worksheet->getStyle('A1')->applyFromArray($styleArray);

        // 将A18到E22合并为一个单元格
        $spreadsheet->getActiveSheet()->mergeCells('A18:E22');
        // 将合并后的单元格拆分
        $spreadsheet->getActiveSheet()->unmergeCells('A18:E22');

        // 将B2至G8的区域添加红色边框。
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THICK,
                    'color' => ['argb' => Color::COLOR_RED],
                ],
            ],
        ];
        $worksheet->getStyle('B2:G8')->applyFromArray($styleArray);

        // 设置当前工作表标题。
        $spreadsheet->getActiveSheet()->setTitle('Hello');

        // 设置日期格式。
        $spreadsheet->getActiveSheet()->setCellValue('D1', '2018-06-15');
        $spreadsheet->getActiveSheet()->getStyle('D1')->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_DATE_YYYYMMDD);

        // 使用\n进行单元格内换行，相当于(ALT+"Enter")
        $spreadsheet->getActiveSheet()->getCell('A4')->setValue("hello\nworld");
        $spreadsheet->getActiveSheet()->getStyle('A4')->getAlignment()->setWrapText(true);

        // 将单元格设置为超链接形式
        $spreadsheet->getActiveSheet()->setCellValue('E6', 'www.helloweba.net');
        $spreadsheet->getActiveSheet()->getCell('E6')->getHyperlink()->setUrl('https://www.helloweba.net');

        // 使用SUM计算B5到C5之间单元格的总和。其他函数同理：最大数(MAX)，最小数(MIN)，平均值(AVERAGE)。
        $spreadsheet->getActiveSheet()->setCellValue('B7', '=SUM(B5:C5)');

        // 设置文档属性
        $spreadsheet->getProperties()
            ->setCreator("Helloweba") //作者
            ->setLastModifiedBy("Yuegg") //最后修改者
            ->setTitle("Office 2007 XLSX Test Document") //标题
            ->setSubject("Office 2007 XLSX Test Document") //副标题
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.") //描述
            ->setKeywords("office 2007 openxml php") //关键字
            ->setCategory("Test result file"); //分类


        // 导入
        $spreadsheet = IOFactory::load('tmp.xlsx');
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
//        var_dump($data);
    }
}
