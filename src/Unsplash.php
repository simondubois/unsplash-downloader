<?php namespace Simondubois\UnsplashDownloader;

use Crew\Unsplash\Category;
use Crew\Unsplash\CuratedBatch;
use Crew\Unsplash\HttpClient;
use Crew\Unsplash\Photo;

/**
 * A proxy to deal with the Unsplah API :
 * - connect to the server.
 * - list photos
 * @codeCoverageIgnore
 */
class Unsplash
{

    /**
     * Unsplash application ID from https://unsplash.com/developers
     * @var string
     */
    private $applicationId = '797a14e918f07f3559643a10f7c9e0de9d8a94262cd0ea0eb4b12c6d0993ed50';

    /**
     * Unsplash constructor (start HttpClient)
     */
    public function __construct()
    {
        HttpClient::init([
            'applicationId' => $this->applicationId,
        ]);
    }

    /**
     * Request APi to get last photos
     * @param  int $quantity Number of photos to return
     * @return string[] Photo download links indexed by IDs
     */
    public function allPhotos($quantity)
    {
        $photos = [];

        foreach (Photo::all(1, $quantity) as $photo) {
            $photos[$photo->id] = $photo->links['download'];
        };

        return $photos;
    }

    /**
     * Request APi to get last photos in category
     * @param  int $quantity Number of photos to return
     * @param  integer $category Category ID
     * @return string[] Photo download links indexed by IDs
     */
    public function photosInCategory($quantity, $category)
    {
        $photos = [];

        foreach (Category::find($category)->photos(1, $quantity) as $photo) {
            $photos[$photo->id] = $photo->links['download'];
        };

        return $photos;
    }

    /**
     * Request APi to get last featured photos
     * @param  int $quantity Number of photos to return
     * @return string[] Photo download links indexed by ID
     */
    public function featuredPhotos($quantity)
    {
        $photos = [];

        // process currated batches
        foreach (CuratedBatch::all(1, 100) as $batchInfo) {
            $batch = CuratedBatch::find($batchInfo->id);

            // process photos
            foreach ($batch->photos() as $photo) {
                $photos[$photo->id] = $photo->links['download'];

                // quit if $quantity photos have been found
                if (count($photos) >= $quantity) {
                    break 2;
                }
            }
        }

        return $photos;
    }

    /**
     * Request APi to get all categories photos
     * @return string[] Category names indexed by IDs
     */
    public function allCategories()
    {
        $categories = [];

        foreach (Category::all() as $category) {
            $categories[$category->id] = $category->title;
        };

        return $categories;
    }
}
