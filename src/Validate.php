<?php namespace Simondubois\UnsplashDownloader;

use Exception;
use InvalidArgumentException;

class Validate {


    //
    // Constants
    //

    /**
     * Error codes
     */
    const ERROR_DESTINATION_NOTDIR     = 1;
    const ERROR_DESTINATION_UNWRITABLE = 2;
    const ERROR_QUANTITY_NOTNUMERIC    = 3;
    const ERROR_QUANTITY_NOTPOSITIVE   = 4;
    const ERROR_QUANTITY_TOOHIGH       = 5;
    const ERROR_HISTORY_NOTFILE        = 6;
    const ERROR_HISTORY_NOTRW          = 7;
    const ERROR_NO_CREDENTIALS         = 8;
    const ERROR_INCORRECT_CREDENTIALS  = 9;



    //
    // API credentials
    //

    /**
     * Valid loaded credentials and assign credentials to the task
     * @param  bool|array $credentials Loaded credentials
     * @param  string $apiCrendentialsPath Path of the credentials file
     */
    public function apiCredentials($credentials, $apiCrendentialsPath) {
        if ($credentials === false) {
            throw new Exception(
                'The credentials file has not been found.'.PHP_EOL
                    .'Please create the file '.$apiCrendentialsPath.' with the following content :'.PHP_EOL
                    .'applicationId = "your-application-id"'.PHP_EOL
                    .'secret = "your-secret"'.PHP_EOL
                    .'Register to https://unsplash.com/developers to get your gredentials.',
                static::ERROR_NO_CREDENTIALS
            );
        }

        if (!isset($credentials['applicationId']) || !isset($credentials['secret'])) {
            throw new Exception(
                'The credentials file is not correct : '
                    .'please check that both applicationId and secret are correctly defined.',
                static::ERROR_INCORRECT_CREDENTIALS
            );
        }
    }



    //
    // Quantity
    //

    /**
     * Check validity of a quantity
     * @param  string $value Value to validate
     * @return int Validated value
     */
    public function quantity($value)
    {
        $value = $this->quantityFormat($value);

        $this->quantityBoudaries($value);

        return $value;
    }

    /**
     * Format the quantity to integer
     * @param  string $value Parameter value
     * @return int               Formatted quantity value
     */
    private function quantityFormat($value)
    {
        if (is_numeric($value) === false) {
            throw new InvalidArgumentException(
                'The given quantity ('.$value.') is not numeric.',
                self::ERROR_QUANTITY_NOTNUMERIC
            );
        }

        return intval($value);
    }

    /**
     * Check the quantity value
     * @param int $value Formatted quantity value
     */
    private function quantityBoudaries($value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException(
                'The given quantity ('.$value.') is not positive.',
                self::ERROR_QUANTITY_NOTPOSITIVE
            );
        }

        if ($value > 100) {
            throw new InvalidArgumentException(
                'The given quantity ('.$value.') is too high (should not be greater than 100).',
                self::ERROR_QUANTITY_TOOHIGH
            );
        }
    }




    //
    // History
    //

    /**
     * Check validity of the history parameter
     * @param  string $value Parameter value
     * @return null|string     Validated and formatted history value
     */
    public function history($value)
    {
        if (is_null($value)) {
            return null;
        }

        $this->historyValidationType($value);
        $this->historyValidationAccess($value);

        return $value;
    }

    /**
     * Check if history is not a dir
     * @param  string $value Parameter value
     */
    private function historyValidationType($value)
    {
        if (is_dir($value) === true) {
            throw new InvalidArgumentException(
                'The given history path ('.$value.') is not a file.',
                self::ERROR_HISTORY_NOTFILE
            );
        }
    }

    /**
     * Check if history is accessible
     * @param  string $value Parameter value
     */
    private function historyValidationAccess($value)
    {
        $handle = @fopen($value, 'a+');

        if ($handle === false) {
            throw new InvalidArgumentException(
                'The given history path ('.$value.') can not be created or opened for read & write.',
                self::ERROR_HISTORY_NOTRW
            );
        }

        fclose($handle);
    }



    //
    // Destination
    //

    /**
     * Check validity of the destination parameter
     * @param  string $value Parameter value
     * @return string              Validated and formatted destination value
     */
    public function destination($value)
    {
        if (is_dir($value) === false) {
            throw new InvalidArgumentException(
                'The given destination path ('.$value.') is not a directory.',
                self::ERROR_DESTINATION_NOTDIR
            );
        }

        if (is_writable($value) === false) {
            throw new InvalidArgumentException(
                'The given destination path ('.$value.') is not writable.',
                self::ERROR_DESTINATION_UNWRITABLE
            );
        }

        return $value;
    }

    /**
     * Check validity of the category parameter
     * @param  string $value Parameter value
     * @return string Validated and formatted category value
     */
    public function category($value)
    {
        if (is_null($value)) {
            return null;
        }

        return intval($value);
    }

}
