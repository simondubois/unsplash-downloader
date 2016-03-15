<?php namespace Simondubois\UnsplashDownloader;

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
     * @param  string|null $value Parameter value
     * @return int|null Validated and formatted category value
     */
    public function category($value)
    {
        if (is_null($value)) {
            return null;
        }

        return intval($value);
    }

}
