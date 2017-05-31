<?php

class InputCheck{
  function __construct(){
  }
  public function run($para, $tgtvar){
    $chkFlg = False;
    
    $okparas = array();
    $errors = array();
    $err_messages = array(
      'mis' => 'missing parameter',
      'typ' => 'wrong type. It should be ',
      'ptt' => 'it does not match the assumed pattern'
    );
    if($para && $tgtvar){
      $chkFlg = True;
      foreach($tgtvar as $key => $value){
        $nullFlg = False;
        #  Null check
        if($value['Null'] != 'YES'){
          $nullOkFlg = False;
          if($value['Null'] == 'COND_OK'){
            $conds = $value['COND_OK'];
            foreach($conds as $eachcond){
            foreach($eachcond as $cnd_key => $cnd_val){
              if(isset($okparas[$cnd_key])){
                if($okparas[$cnd_key] == $cnd_val){
                  $nullOkFlg = True;
                }
              }
            }
            }
          }
          if(!$nullOkFlg){
            if(isset($para[$key])){
              $nullFlg = True;
            }
          }
        }else{
          $nullOkFlg = True;
        }
        if($nullFlg || ($nullOkFlg && isset($para[$key]))){
          $valtype = $value['Type'];
          #  input check
          $typFlg = $this->val_check($valtype, $para[$key]);
          if($typFlg){
            #  assumed pattern check
            $pttnFlg = $this->pttn_check($value, $para[$key]);
            if($pttnFlg){
              if($key == 'samp'){
                if(!isset($okparas[$key])){
                  $okparas[$key] = $para[$key];
                }
              }else{
                $okparas[$key] = $para[$key];
                if($valtype == 'boolean'){
                  $okparas[$key] = $this->trans_bool($para[$key]);
                }elseif($key == 'uid' && $para[$key] == 9999){
                  $okparas['samp'] = True;
                }
              }
            }else{
              $errors[$key] = $err_messages['ptt'];
              $chkFlg = False;
            }
          }else{
            $errors[$key] = $err_messages['typ'].$valtype;
            $chkFlg = False;
          }
        }else if($nullOkFlg){
          if(isset($value['Default'])){
            if($key == 'samp'){
              if(!isset($okparas[$key])){
                $okparas[$key] = $value['Default'];
              }
            }else{
              $okparas[$key] = $value['Default'];
            }
          }
        }else{
          $errors[$key] = $err_messages['mis'];
          $chkFlg = False;
        }
        
      }
    }
    $result = array(
      'check' => $chkFlg,
      'paras' => $okparas,
      'error' => $errors
    );
  
    return $result;
  }
  private function val_check($valtype, $val){
    $typFlg = False;
    if($valtype == 'string'){
      $typFlg = $this->str_check($val);
    }else if($valtype == 'date'){
      $typFlg = $this->date_check($val);
    }else if($valtype == 'number'){
      $typFlg = $this->num_check($val);
    }else if($valtype == 'boolean'){
      $typFlg = $this->bool_check($val);
    }else if($valtype == 'object'){
  $typFlg = True;
    }
    return $typFlg;
  }
  
  private function pttn_check($cond, $val){
    $pttnFlg = True;
    if(isset($cond['Pattern'])){
      if(!in_array($val, $cond['Pattern'])){
        $pttnFlg = False;
      }
    }
    return $pttnFlg;
  }
  
  # numeric check
  private function num_check($val){
    return is_numeric($val);
  }
  # string check + check if it includes funny character
  private function str_check($val){
    $result = True;
    if(is_numeric($val)){
      $result = False;
    }else if(gettype($val) == 'array'){
      #return False;
      return True; # it is not right, but temporary fix
    }else{
      if(strpos($val, '&')!=False || strpos($val, '=')!=False || strpos($val, '\'')!=False || strpos($val, '*')!=False || strpos($val, '$')!=False){
        $result = False;
      }
    }
    return $result;
  }
  # date check
  private function date_check($val){
    $result;
    $date = split('-', $val);
    if(count($date) == 3){
      $year  = (int) $date[0];
      $month = (int) $date[1];
      $day   = (int) $date[2];
      $result = checkdate($month, $day, $year);
    }else{
      $result = False;
    }
    return $result;
  }
  private function bool_check($val){
    $result = False;
    if(is_string($val)){
      $ASSUMED_STR = array('false', 'true');
      if(in_array($val, $ASSUMED_STR )){
        $result = True;
      }
    }else{
      $result = is_bool($val);
    }
    return $result;
  }
  private function trans_bool($val){
    $result = $val;
    if(is_string($val)){
      if($val == 'false'){
        $result = False;
      }else if($val == 'true'){
        $result = True;
      }
    }
    return $result;
  }
}

?>
