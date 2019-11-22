<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2019/11/18            #
# -------------------------- #
namespace Structure\Helper;

use Throwable;

class StructureException extends \InvalidArgumentException{
    const UNKNOWN_EXCEPTION             = 'ex000_|Unknown exception';
    const INVALID_FILTER_SPECIFIED      = 'ex001a|Invalid filter specified: ';
    const CLASS_MUST_BE_HANDLE_INSTANCE = 'ex002a|Class must be handle instance';

    /**
     * StructureException constructor.
     * @param $exception
     * @param Throwable|null $previous
     */
    public function __construct($exception, Throwable $previous = null) {
        list($bool,$exception) = self::_explode($exception);
        if(!$bool){
            list($bool,$exception) = self::_explode(self::UNKNOWN_EXCEPTION);
        }
        list($message,$code) = $exception;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param string $exception
     * @return string
     */
    public static function Code(string $exception){
        list($bool,$exception) = self::_explode($exception);
        if($bool){
            return $exception[0];
        }
        return 'ex000_';
    }

    public static function Message(string $exception){
        list($bool,$exception) = self::_explode($exception);
        if($bool){
            return $exception[1];
        }
        return "Unknown exception[{$exception}]";
    }

    private static function _explode(string $string){
        $string = explode('|',$string);
        if(count($string) > 1){
            return [true,$string];
        }
        return [false,$string];
    }
}