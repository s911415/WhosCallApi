<?php
function showError(int $code, string $message = ''){
    http_response_code($code);
    echo json_encode(['success'=>false,'result' => $message]);
    die;
}

function getTimesFromList($parent) : array
{
    $res = [];
    if($parent){
        foreach($parent->children as $c){
            $text = getPlainTextOrDefault($c);
            if(!empty($text)) $res[] = $text;
        }
    }
    
    return $res;
}

function getPlainTextOrDefault($element, string $default = '') : string {
    if($element) return trim($element->getPlainText());
    
    return $default;
}

function getAttributeOrDefault($element, $attr, string $default = '') : string {
    
    if($element){
        $val = $element->getAttribute($attr);
        if($val!==null) return $val;
    }
    
    return $default;
}

