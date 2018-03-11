<?php

use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\Assets\FileNameFilter;
use SilverStripe\Assets\Image;

class SimplePdfPreviewImageExtension extends DataExtension
{

    private $generator;
    private $folderToSave;
    private $imagePrefix;

//@Todo cant get Silverstripe DI to work with constructor injection
//    function __construct(SimplePdfPreviewGeneratorInterface $generator,
//                         $folderToSave = null,
//                         $imagePrefix = null,
//                         $savePath = null)
//    {
//        $this->generator = $generator;
//        $this->folderToSave = $folderToSave;
//        $this->imagePrefix = $imagePrefix;
//        $this->savePath = $savePath;
//    }

    public function getPdfPreviewImage()
    {
        //$pdfFile = Director::getAbsFile($this->owner->getFileName());
        $pdfFile = Director::getAbsFile('assets/.protected/' . $this->owner->getMetaData()['path']);
        $pathInfo = pathinfo($pdfFile);
        if (strtolower($pathInfo['extension']) != 'pdf') {
            //@Todo if dev then exception? else fail silently
            return null;
        }
        $fileName = $pathInfo['filename'];

        $saveImage = $this->imagePrefix . '-' . $fileName . '.jpg';

        // Fix illegal characters
        $filter = FileNameFilter::create();
        $saveImage = $filter->filter($saveImage);
        $tmpFile = tempnam("/tmp", "pdf");

        $image = DataObject::get_one('SilverStripe\Assets\Image', "`Name` = '{$saveImage}'");

        if (!$image || true) {
            $folderObject = DataObject::get_one("SilverStripe\Assets\Folder", "`Name` = '{$this->folderToSave}'");
            if ($folderObject) {

            	if ($this->generator->generatePreviewImage($pdfFile, $tmpFile)) {
                    $image = new Image();
                    $image->setFromLocalFile($tmpFile, $saveImage);
                    $image->ParentID = $folderObject->ID;
                    $image->write();
                }
            }
        } else {
            //check LastEdited to update
            $cacheInValid = false;
            if (strtotime($image->LastEdited) < strtotime($this->owner->LastEdited)) {
                $cacheInValid = true;
            }
            if ($cacheInValid) {
                $this->generator->generatePreviewImage($pdfFile, $tmpFile);
                $image->setFromLocalFile($tmpFile, $saveImage);
                $image->write(false, false, true);
            }
        }
        
        unlink($tmpFile);
        return $image;
    }

    /**
     * @param $folderToSave
     */
    public function setFolderToSave($folderToSave)
    {
        $this->folderToSave = $folderToSave;
    }

    /**
     * @param \SimplePdfPreviewGeneratorInterface $generator
     */
    public function setGenerator(\SimplePdfPreviewGeneratorInterface $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param $imagePrefix
     */
    public function setImagePrefix($imagePrefix)
    {
        $this->imagePrefix = $imagePrefix;
    }

}
