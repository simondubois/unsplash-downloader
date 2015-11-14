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
        $quantity = $this->quantityFormat($value);

        $this->quantityBoudaries($quantity);

        return $quantity;
    }

    /**
     * Format the quantity to integer
     * @param  string $parameter Parameter value
     * @return int               Formatted quantity value
     */
    private function quantityFormat($parameter)
    {
        if (is_numeric($parameter) === false) {
            throw new InvalidArgumentException(
                'The given quantity ('.$parameter.') is not numeric.',
                self::ERROR_QUANTITY_NOTNUMERIC
            );
        }

        return intval($parameter);
    }

    /**
     * Check the quantity value
     * @param int $quantity Formatted quantity value
     */
    private function quantityBoudaries($quantity)
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException(
                'The given quantity ('.$quantity.') is not positive.',
                self::ERROR_QUANTITY_NOTPOSITIVE
            );
        }

        if ($quantity > 100) {
            throw new InvalidArgumentException(
                'The given quantity ('.$quantity.') is too high (should not be greater than 100).',
                self::ERROR_QUANTITY_TOOHIGH
            );
        }
    }




    //
    // History
    //

    /**
     * Check validity of the history parameter
     * @param  string $history Parameter value
     * @return null|string     Validated and formatted history value
     */
    public function history($history)
    {
        if (is_null($history)) {
            return null;
        }

        $this->historyValidationType($history);
        $this->historyValidationAccess($history);

        return $history;
    }

    /**
     * Check if history is not a dir
     * @param  string $history Parameter value
     */
    private function historyValidationType($history)
    {
        if (is_dir($history) === true) {
            throw new InvalidArgumentException(
                'The given history path ('.$history.') is not a file.',
                self::ERROR_HISTORY_NOTFILE
            );
        }
    }

    /**
     * Check if history is accessible
     * @param  string $history Parameter value
     */
    private function historyValidationAccess($history)
    {
        $handle = @fopen($history, 'a+');

        if ($handle === false) {
            throw new InvalidArgumentException(
                'The given history path ('.$history.') can not be created or opened for read & write.',
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
     * @param  string $destination Parameter value
     * @return string              Validated and formatted destination value
     */
    public function destination($destination)
    {
        if (is_dir($destination) === false) {
            throw new InvalidArgumentException(
                'The given destination path ('.$destination.') is not a directory.',
                self::ERROR_DESTINATION_NOTDIR
            );
        }

        if (is_writable($destination) === false) {
            throw new InvalidArgumentException(
                'The given destination path ('.$destination.') is not writable.',
                self::ERROR_DESTINATION_UNWRITABLE
            );
        }

        return $destination;
    }
}
