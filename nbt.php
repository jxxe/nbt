<?php

/**
 * A class for reading NBT files.
 * 
 * @link https://github.com/jxxe/nbt
 */

PHP_INT_SIZE === 8 or trigger_error('This class only runs on 64-bit systems.', E_USER_ERROR);

class NBT {

    public $result = [];
    protected $file;
    public $debug = false;

    const TAG_END = 0;
    const TAG_BYTE = 1;
    const TAG_SHORT = 2;
    const TAG_INT = 3;
    const TAG_LONG = 4;
    const TAG_FLOAT = 5;
    const TAG_DOUBLE = 6;
    const TAG_BYTE_ARRAY = 7;
    const TAG_STRING = 8;
    const TAG_LIST = 9;
    const TAG_COMPOUND = 10;

    protected function debug($message) {
        if($this->debug) {
            echo $message . '<br>';
        }
    }

    public function loadString($string) {
        is_string($string) or trigger_error('NBT data must be a string', E_USER_ERROR);
        $this->debug('Writing string to virtual file');

        $this->file = fopen('php://temp', 'r+'); // Open virtual file with pointer at the beginning of the file
        fwrite($this->file, $string); // Write string to virtual file
        rewind($this->file); // Return pointer to beginning of virtual file

        $this->debug('Reading first tag in file');
        $this->walkTag($this->result); // Begin talk walking
        $this->debug('Encountered end byte for first tag');

        return $this->result;
    }

    public function clear() {
        $this->debug('Clearing all loaded data');
        $this->result = [];
    }

    protected function walkTag(&$tree) { // & means that it is a reference to the original variable, so it will modifiy it instead of creating a copy
        if( feof($this->file) ) {
            $this->debug('Encountered end of file');
            return false; // If end of file, stop looping
        }

        $tagType = $this->readTag(self::TAG_BYTE); // Read tag type (see tagType spec)

        if( $tagType == self::TAG_END ) {
            $this->debug('Encountered TAG_END');
            return false; // If end tag, stop looping (see TAG_Compound spec)
        }

        $tagName = $this->readTag(self::TAG_STRING); // Read tag name (see TAG_Compound spec)
        $tagData = $this->readTag($tagType); // Read tag data based on type
        $pointerPosition = ftell($this->file);
        $this->debug("Reading tag \"$tagName\" at byte $pointerPosition");
        $tree[$tagName] = $tagData; // Add data in key-value format, using tag name as key

        return true; // Keep Unpacked since not end of file or end of tag
    }

    public function readTag($tagType) {
        switch($tagType) {
            case self::TAG_BYTE:
                return unpack( 'c', fread($this->file, 1) )[1];

            case self::TAG_SHORT:
                $short = unpack( 'n', fread($this->file, 2) )[1]; // Read big-endian short, 16 bits (n)
                if( $short > 2**15 ) $short -= 2**16;
                return $short;

            case self::TAG_INT:
                $int = unpack( 'N', fread($this->file, 4) )[1]; // Read unsigned long, 32 bits (N)
                if( $int >= 2**31 ) $int -= 2**32;
                return $int;

            case self::TAG_LONG:
                $long = unpack( 'J', fread($this->file, 8) )[1]; // Read unsigned long long, 64 bits (J)
                if( $long >= 2**63 ) $long -= 2**64;
                return $long;

            case self::TAG_FLOAT:
                if( pack('d', 1) == "\77\360\0\0\0\0\0\0" ) {
                    return unpack( 'f', fread($this->file, 4) )[1];
                } else {
                    return unpack( 'f', strrev( fread($this->file, 4) ) )[1];
                }

            case self::TAG_DOUBLE:
                if( pack('d', 1) == "\77\360\0\0\0\0\0\0" ) {
                    return unpack( 'd', fread($this->file, 8) )[1];
                } else {
                    return unpack( 'd', strrev( fread($this->file, 8) ) )[1];
                }

            case self::TAG_BYTE_ARRAY:
                $arrayLength = $this->readTag(self::TAG_INT); // Get length of byte array (specified by TAG_Int)
                $byteArray = []; // @todo Instead of using a for loop, unpack $arrayLength of bytes and explode() into array?
                for( $i = 0; $i < $arrayLength; $i++ ) {
                    $byteArray[] = $this->readTag(self::TAG_BYTE); // Read each byte
                }
                return $byteArray;

            case self::TAG_STRING:
                $stringLength = $this->readTag(self::TAG_SHORT); // Get length of string
                return $stringLength > 0 ? fread($this->file, $stringLength) : ''; // If the string has any length, read it



            case self::TAG_LIST:
                $listTagType = $this->readTag(self::TAG_BYTE);
                $listLength = $this->readTag(self::TAG_INT);
                $list = [];
                for( $i = 0; $i < $listLength; $i++ ) {
                    if( feof($this->file) ) break; // If end of file is reached, stop the readTag() method
                    $list[] = $this->readTag($listTagType);
                }
                return $list;

            case self::TAG_COMPOUND:
                $tree = [];
                while( $this->walkTag($tree) );
                return $tree;
        }
    }
}
