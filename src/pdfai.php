<?php

class PDFAI
{
    public $directory_tmp;
    public $compatibleFilePath;
    public $fileFilledPath;
    public $pdfFileImagesPath;
    public $uniqid;

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
            exec("pdftk $filePDFOriginal output " . $this->compatibleFilePath);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function insertData($fields = [])
    {
        try {
            $pdf = new FPDM($this->compatibleFilePath);
            $pdf->Load($fields, true);
            $pdf->Merge();
            $pdf->Output('F', $this->fileFilledPath);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function mergePdfWithImages($fieldImages = [], $filePDFOutput = 'output.pdf')
    {
        try {
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
            // die;
            // foreach($fieldImages as $fieldImage){
            //     if($fieldImage['imageBase64']!=""){
            //         $imageContent = file_get_contents($fieldImage['imageBase64']);
            //         $path = tempnam(sys_get_temp_dir(), 'prefix');
            //         file_put_contents ($path, $imageContent);
            //         $pdfImage->Image($path,$fieldImage['x'],$fieldImage['y'],$fieldImage['w'],$fieldImage['h'],'PNG');
            //     }
            // }

            $pdfImage->Output('F', $this->pdfFileImagesPath);

            exec("pdftk {$this->fileFilledPath} multistamp {$this->pdfFileImagesPath} output $filePDFOutput");
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getDataFields(){
        exec("pdftk {$this->compatibleFilePath} dump_data_fields",$output);
        return $output;
    }

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
