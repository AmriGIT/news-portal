<?php

namespace App\Data;

readonly class SeoData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonicalUrl,
        public bool $robotsIndex,
        public bool $robotsFollow,
        public string $ogTitle,
        public ?string $ogDescription,
        public ?string $ogImage,
        public string $ogType,
        public string $twitterCard,
        public ?string $ogImageAlt = null,
        public ?string $articlePublishedTime = null,
        public ?string $articleModifiedTime = null,
        public ?string $articleSection = null,
        /** @var array<int, string> */
        public array $articleTags = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonicalUrl' => $this->canonicalUrl,
            'robotsIndex' => $this->robotsIndex,
            'robotsFollow' => $this->robotsFollow,
            'ogTitle' => $this->ogTitle,
            'ogDescription' => $this->ogDescription,
            'ogImage' => $this->ogImage,
            'ogType' => $this->ogType,
            'twitterCard' => $this->twitterCard,
            'ogImageAlt' => $this->ogImageAlt,
            'articlePublishedTime' => $this->articlePublishedTime,
            'articleModifiedTime' => $this->articleModifiedTime,
            'articleSection' => $this->articleSection,
            'articleTags' => $this->articleTags,
        ];
    }
}
