<?php
/**
 * Class that allows creating a pdf file with fields filled with an acroform with images in base64
 *
 * Require: pdftk installed in the server
 * Author Ronald Nina - ronald.nina.layme@gmail.com
 */
class PDFAI
{
    public $directory_tmp;
    public $compatibleFilePath;
    public $fileFilledPath;
    public $pdfFileImagesPath;
    public $uniqid;
    public $filePDFOutput;

    /**
     * Undocumented function
     *
     * @param string $filePDFOriginal example: "/folder/fileacroform.pdf"
     */
    public function __construct($filePDFOriginal)
    {
        try {
            // Convert PDF compatible to pdftk
            if (!file_exists($filePDFOriginal)) {
                die('File not found');
            }
            $this->uniqid = uniqid();
            $this->directory_tmp = sys_get_temp_dir();
            $pathseparator = explode("/", $filePDFOriginal);
            $nameFileOriginal = $pathseparator[count($pathseparator) - 1];

            $this->compatibleFilePath = $this->directory_tmp . DIRECTORY_SEPARATOR . "compatible_" . $this->uniqid . $nameFileOriginal;
            $this->fileFilledPath = $this->directory_tmp . DIRECTORY_SEPARATOR . "filled_" . $this->uniqid . $nameFileOriginal;
            $this->pdfFileImagesPath = $this->directory_tmp . DIRECTORY_SEPARATOR . "pdf_with_images_" . $this->uniqid . $nameFileOriginal;
            $this->filePDFOutput = $this->directory_tmp . DIRECTORY_SEPARATOR . "output_" . $this->uniqid . $nameFileOriginal;
            exec("pdftk $filePDFOriginal output " . $this->compatibleFilePath);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * insertData in fields of the pdf
     *
     * @param array $fields             no required
     * @param string $fileFilledPath    no required
     * @return string path generate file
     *
     * Example: $fields = [
     *                   'field1' => 'value1',
     *                   'field2' => 'value2',
     *                   'field3' => 'value3',
     *                   'field4' => 'value4',
     *               ];
     *          $fileFilledPath = "/folder/output.pdf"
     */
    public function insertData($fields = [], $fileFilledPath = '')
    {
        try {
            if ($fileFilledPath != '') {
                $this->fileFilledPath = $fileFilledPath;
            }
            $pdf = new FPDM($this->compatibleFilePath);
            $pdf->Load($fields, true);
            $pdf->Merge();
            $pdf->Output('F', $this->fileFilledPath);
            return $this->fileFilledPath;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Inserted the images in PDF per page configurable the axis position  x, y, with and height
     *
     * @param array $fieldImages
     * @param string $filePDFOutput
     * @return string path generate with images file
     * Example
     *          $fieldImages = [
     *              'number_page' => [
     *                                     [
     *                                         'imageBase64' => 'base64',
     *                                          'imageType' => 'typeimange',
     *                                          'x' => 10,
     *                                          'y' => 190,
     *                                          'w' => 0,
     *                                          'h' => 0,
     *                                     ],
     *                                     [
     *                                         'imageBase64' => 'base64',
     *                                          'imageType' => 'typeimange',
     *                                          'x' => 10,
     *                                          'y' => 190,
     *                                          'w' => 0,
     *                                          'h' => 0,
     *                                     ],
     *                                  ]
     *          ]
     */
    public function mergePdfWithImages($fieldImages = [], $filePDFOutput = '')
    {
        try {
            if($filePDFOutput != ''){
                $this->filePDFOutput = $filePDFOutput;
            }
            asort($fieldImages);
            $pdfImage = new Fpdf\Fpdf('P', 'mm', 'letter');

            $indexes = array_keys($fieldImages);
            $max = max($indexes);
            for ($i = 1; $i <= $max; $i++) {
                $pdfImage->AddPage();
                if (isset($fieldImages[$i])) {

                    foreach ($fieldImages[$i] as $fieldImage) {
                        if ($fieldImage['imageBase64'] != "") {
                            $imageContent = file_get_contents($fieldImage['imageBase64']);
                            $path = tempnam(sys_get_temp_dir(), 'prefix');
                            file_put_contents($path, $imageContent);
                            $pdfImage->Image($path, $fieldImage['x'], $fieldImage['y'], $fieldImage['w'], $fieldImage['h'], 'PNG');
                            unlink($path);
                        }
                    }
                }
            }

            $pdfImage->Output('F', $this->pdfFileImagesPath);

            exec("pdftk {$this->fileFilledPath} multistamp {$this->pdfFileImagesPath} output {$this->filePDFOutput}");
            return $this->filePDFOutput;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * get information about the fields
     *
     * @return array information about the fields
     */
    public function getDataFields()
    {
        exec("pdftk {$this->compatibleFilePath} dump_data_fields", $output);
        return $output;
    }

    /**
     * Clean the files used
     */
    public function __destruct()
    {
        try {
            if (file_exists($this->compatibleFilePath)) {
                unlink($this->compatibleFilePath);
            }
            if (file_exists($this->fileFilledPath)) {
                unlink($this->fileFilledPath);
            }
            if (file_exists($this->pdfFileImagesPath)) {
                unlink($this->pdfFileImagesPath);
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
