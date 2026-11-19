<?php

namespace DiakonieSH\DshMastodon\Service;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\FileReference as CoreFileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Service\ImageProcessingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

readonly class ImageService
{
    public function __construct(
        protected IconFactory $iconFactory,
        protected ImageProcessingService $processingService,
    ) {}

    public function thumbnail(FileReference $fileReference): string
    {
        $fileReferenceObject = $fileReference->getOriginalResource();
        $fileObject = $fileReferenceObject->getOriginalFile();

        if ($fileObject->isMissing()) {
            return $this->iconFactory
                ->getIcon('mimetypes-other-other', IconSize::MEDIUM, 'overlay-missing')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing') . ' ' . $fileObject->getName())
                ->render();
        }

        // Preview web image or media elements
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && ($fileObject->isImage() || $fileObject->isMediaFile())
        ) {
            $processedImage = $this->resize($fileReferenceObject, 64, 64);
            $attributes = [
                'src' => $processedImage->getPublicUrl() ?? '',
                'width' => $processedImage->getProperty('width'),
                'height' => $processedImage->getProperty('height'),
                'alt' => $fileReferenceObject->getAlternative() ?: $fileReferenceObject->getName(),
                'loading' => 'lazy',
            ];
            $imgTag = '<img ' . GeneralUtility::implodeAttributes($attributes, true) . '/>';
        } else {
            $imgTag = $this->iconFactory->getIconForResource($fileObject)->setTitle($fileObject->getName())->render();
        }

        return $imgTag;
    }

    public function resize(CoreFileReference $fileReferenceObject, int $maxWidth, int $maxHeight): ProcessedFile
    {
        $cropVariantCollection = CropVariantCollection::create((string)$fileReferenceObject->getProperty('crop'));
        $cropArea = $cropVariantCollection->getCropArea();
        $processingConfiguration = [
            'maxWidth' => $maxWidth,
            'maxHeight' => $maxHeight,
        ];

        if (!$cropArea->isEmpty()) {
            $processingConfiguration = [
                'maxWidth' => $maxWidth,
                'maxHeight' => $maxHeight,
                'crop' => $cropArea->makeAbsoluteBasedOnFile($fileReferenceObject),
            ];
        }

        $processedImage = $fileReferenceObject
            ->getOriginalFile()
            ->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingConfiguration);

        if (!$processedImage->exists()) {
            $this->processingService->process($processedImage->getUid());
        }

        return $processedImage;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
