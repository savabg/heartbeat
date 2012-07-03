<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaVa
 * Date: 7/3/12
 * Time: 1:49 AM
 * To change this template use File | Settings | File Templates.
 */
namespace TBS;
class clsTbsDataSource {

    public $Type = false;
    public $SubType = 0;
    public $SrcId = false;
    public $Query = '';
    public $RecSet = false;
    public $RecKey = '';
    public $RecNum = 0;
    public $RecNumInit = 0;
    public $RecSaving = false;
    public $RecSaved = false;
    public $RecBuffer = false;
    public $CurrRec = false;
    public $TBS = false;
    public $OnDataOk = false;
    public $OnDataPrm = false;
    public $OnDataPrmDone = array();
    public $OnDataPi = false;

    public function DataAlert($Msg) {
        return $this->TBS->meth_Misc_Alert('when merging block '.$this->TBS->_ChrOpen.$this->TBS->_CurrBlock.$this->TBS->_ChrClose,$Msg);
    }

    public function DataPrepare(&$SrcId,&$TBS) {

        $this->SrcId = &$SrcId;
        $this->TBS = &$TBS;
        $FctInfo = false;
        $FctObj = false;

        if (is_array($SrcId)) {
            $this->Type = 0;
        } elseif (is_resource($SrcId)) {

            $Key = get_resource_type($SrcId);
            switch ($Key) {
                case 'mysql link'            : $this->Type = 6; break;
                case 'mysql link persistent' : $this->Type = 6; break;
                case 'mysql result'          : $this->Type = 6; $this->SubType = 1; break;
                case 'pgsql link'            : $this->Type = 7; break;
                case 'pgsql link persistent' : $this->Type = 7; break;
                case 'pgsql result'          : $this->Type = 7; $this->SubType = 1; break;
                case 'sqlite database'       : $this->Type = 8; break;
                case 'sqlite database (persistent)'	: $this->Type = 8; break;
                case 'sqlite result'         : $this->Type = 8; $this->SubType = 1; break;
                default :
                    $FctInfo = $Key;
                    $FctCat = 'r';
            }

        } elseif (is_string($SrcId)) {

            switch (strtolower($SrcId)) {
                case 'array' : $this->Type = 0; $this->SubType = 1; break;
                case 'clear' : $this->Type = 0; $this->SubType = 3; break;
                case 'mysql' : $this->Type = 6; $this->SubType = 2; break;
                case 'text'  : $this->Type = 2; break;
                case 'num'   : $this->Type = 1; break;
                default :
                    $FctInfo = $SrcId;
                    $FctCat = 'k';
            }

        } elseif ($SrcId instanceof Iterator) {
            $this->Type = 9; $this->SubType = 1;
        } elseif ($SrcId instanceof ArrayObject) {
            $this->Type = 9; $this->SubType = 2;
        } elseif ($SrcId instanceof IteratorAggregate) {
            $this->Type = 9; $this->SubType = 3;
        } elseif ($SrcId instanceof MySQLi) {
            $this->Type = 10;
        } elseif ($SrcId instanceof PDO) {
            $this->Type = 11;
        } elseif ($SrcId instanceof Zend_Db_Adapter_Abstract) {
            $this->Type = 12;
        } elseif (is_object($SrcId)) {
            $FctInfo = get_class($SrcId);
            $FctCat = 'o';
            $FctObj = &$SrcId;
            $this->SrcId = &$SrcId;
        } elseif ($SrcId===false) {
            $this->DataAlert('the specified source is set to FALSE. Maybe your connection has failed.');
        } else {
            $this->DataAlert('unsupported variable type : \''.gettype($SrcId).'\'.');
        }

        if ($FctInfo!==false) {
            $ErrMsg = false;
            if ($TBS->meth_Misc_UserFctCheck($FctInfo,$FctCat,$FctObj,$ErrMsg,false)) {
                $this->Type = $FctInfo['type'];
                if ($this->Type!==5) {
                    if ($this->Type===4) {
                        $this->FctPrm = array(false,0);
                        $this->SrcId = &$FctInfo['open'][0];
                    }
                    $this->FctOpen  = &$FctInfo['open'];
                    $this->FctFetch = &$FctInfo['fetch'];
                    $this->FctClose = &$FctInfo['close'];
                }
            } else {
                $this->Type = $this->DataAlert($ErrMsg);
            }
        }

        return ($this->Type!==false);

    }

    public function DataOpen(&$Query,$QryPrms=false) {

        // Init values
        unset($this->CurrRec); $this->CurrRec = true;
        if ($this->RecSaved) {
            $this->FirstRec = true;
            unset($this->RecKey); $this->RecKey = '';
            $this->RecNum = $this->RecNumInit;
		if ($this->OnDataOk) $this->OnDataArgs[1] = &$this->CurrRec;
		return true;
	}
        unset($this->RecSet); $this->RecSet = false;
        $this->RecNumInit = 0;
        $this->RecNum = 0;

        if (isset($this->TBS->_piOnData)) {
            $this->OnDataPi = true;
            $this->OnDataPiRef = &$this->TBS->_piOnData;
            $this->OnDataOk = true;
        }
        if ($this->OnDataOk) {
            $this->OnDataArgs = array();
		$this->OnDataArgs[0] = &$this->TBS->_CurrBlock;
		$this->OnDataArgs[1] = &$this->CurrRec;
		$this->OnDataArgs[2] = &$this->RecNum;
		$this->OnDataArgs[3] = &$this->TBS;
	}

        switch ($this->Type) {
            case 0: // Array
                if (($this->SubType===1) and (is_string($Query))) $this->SubType = 2;
                if ($this->SubType===0) {
                    $this->RecSet = &$this->SrcId;
                } elseif ($this->SubType===1) {
                    if (is_array($Query)) {
                        $this->RecSet = &$Query;
                    } else {
                        $this->DataAlert('type \''.gettype($Query).'\' not supported for the Query Parameter going with \'array\' Source Type.');
                    }
                } elseif ($this->SubType===2) {
                    // TBS query string for array and objects, syntax: "var[item1][item2]->item3[item4]..."
                    $x = trim($Query);
                    $z = chr(0);
                    $x = str_replace(array(']->','][','->','['),$z,$x);
                    if (substr($x,strlen($x)-1,1)===']') $x = substr($x,0,strlen($x)-1);
                    $ItemLst = explode($z,$x);
                    $ItemNbr = count($ItemLst);
                    $Item0 = &$ItemLst[0];
                    // Check first item
                    if ($Item0[0]==='~') {
                        $Item0 = substr($Item0,1);
                        if ($this->TBS->ObjectRef!==false) {
                            $Var = &$this->TBS->ObjectRef;
                            $i = 0;
                        } else {
                            $i = $this->DataAlert('invalid query \''.$Query.'\' because property ObjectRef is not set.');
                        }
                    } else {
                        if (isset($this->TBS->VarRef[$Item0])) {
                            $Var = &$this->TBS->VarRef[$Item0];
                            $i = 1;
                        } else {
                            $i = $this->DataAlert('invalid query \''.$Query.'\' because VarRef item \''.$Item0.'\' is not found.');
                        }
                    }
                    // Check sub-items
                    $Empty = false;
                    while (($i!==false) and ($i<$ItemNbr) and ($Empty===false)) {
                        $x = $ItemLst[$i];
                        if (is_array($Var)) {
                            if (isset($Var[$x])) {
                                $Var = &$Var[$x];
                            } else {
                                $Empty = true;
                            }
                        } elseif (is_object($Var)) {
                            $ArgLst = $this->TBS->f_Misc_CheckArgLst($x);
                            if (method_exists($Var,$x)) {
                                $f = array(&$Var,$x); unset($Var);
                                $Var = call_user_func_array($f,$ArgLst);
                            } elseif (property_exists(get_class($Var),$x)) {
                                if (isset($Var->$x)) $Var = &$Var->$x;
                            } elseif (isset($Var->$x)) {
                                $Var = $Var->$x; // useful for overloaded property
                            } else {
                                $Empty = true;
                            }
                        } else {
                            $i = $this->DataAlert('invalid query \''.$Query.'\' because item \''.$ItemLst[$i].'\' is neither an Array nor an Object. Its type is \''.gettype($Var).'\'.');
                        }
                        if ($i!==false) $i++;
                    }
                    // Assign data
                    if ($i!==false) {
                        if ($Empty) {
                            $this->RecSet = array();
                        } else {
                            $this->RecSet = &$Var;
                        }
                    }
                } elseif ($this->SubType===3) { // Clear
                    $this->RecSet = array();
                }
                // First record
                if ($this->RecSet!==false) {
                    $this->RecNbr = $this->RecNumInit + count($this->RecSet);
                    $this->FirstRec = true;
                    $this->RecSaved = true;
                    $this->RecSaving = false;
                }
                break;
            case 6: // MySQL
                switch ($this->SubType) {
                    case 0: $this->RecSet = @mysql_query($Query,$this->SrcId); break;
                    case 1: $this->RecSet = $this->SrcId; break;
                    case 2: $this->RecSet = @mysql_query($Query); break;
                }
                if ($this->RecSet===false) $this->DataAlert('MySql error message when opening the query: '.mysql_error());
                break;
            case 1: // Num
                $this->RecSet = true;
                $this->NumMin = 1;
                $this->NumMax = 1;
                $this->NumStep = 1;
                if (is_array($Query)) {
                    if (isset($Query['min'])) $this->NumMin = $Query['min'];
                    if (isset($Query['step'])) $this->NumStep = $Query['step'];
                    if (isset($Query['max'])) {
                        $this->NumMax = $Query['max'];
                    } else {
                        $this->RecSet = $this->DataAlert('the \'num\' source is an array that has no value for the \'max\' key.');
                    }
                    if ($this->NumStep==0) $this->RecSet = $this->DataAlert('the \'num\' source is an array that has a step value set to zero.');
                } else {
                    $this->NumMax = ceil($Query);
                }
                if ($this->RecSet) {
                    if ($this->NumStep>0) {
                        $this->NumVal = $this->NumMin;
                    } else {
                        $this->NumVal = $this->NumMax;
                    }
                }
                break;
            case 2: // Text
                if (is_string($Query)) {
                    $this->RecSet = &$Query;
                } else {
                    $this->RecSet = ''.$Query;
                }
                break;
            case 3: // Custom function
                $FctOpen = $this->FctOpen;
                $this->RecSet = $FctOpen($this->SrcId,$Query,$QryPrms);
                if ($this->RecSet===false) $this->DataAlert('function '.$FctOpen.'() has failed to open query {'.$Query.'}');
                break;
            case 4: // Custom method from ObjectRef
                $this->RecSet = call_user_func_array($this->FctOpen,array(&$this->SrcId,&$Query,&$QryPrms));
                if ($this->RecSet===false) $this->DataAlert('method '.get_class($this->FctOpen[0]).'::'.$this->FctOpen[1].'() has failed to open query {'.$Query.'}');
                break;
            case 5: // Custom method of object
                $this->RecSet = $this->SrcId->tbsdb_open($this->SrcId,$Query,$QryPrms);
                if ($this->RecSet===false) $this->DataAlert('method '.get_class($this->SrcId).'::tbsdb_open() has failed to open query {'.$Query.'}');
                break;
            case 7: // PostgreSQL
                switch ($this->SubType) {
                    case 0: $this->RecSet = @pg_query($this->SrcId,$Query); break;
                    case 1: $this->RecSet = $this->SrcId; break;
                }
                if ($this->RecSet===false) $this->DataAlert('PostgreSQL error message when opening the query: '.pg_last_error($this->SrcId));
                break;
            case 8: // SQLite
                switch ($this->SubType) {
                    case 0: $this->RecSet = @sqlite_query($this->SrcId,$Query); break;
                    case 1: $this->RecSet = $this->SrcId; break;
                }
                if ($this->RecSet===false) $this->DataAlert('SQLite error message when opening the query:'.sqlite_error_string(sqlite_last_error($this->SrcId)));
                break;
            case 9: // Iterator
                if ($this->SubType==1) {
                    $this->RecSet = $this->SrcId;
                } else { // 2 or 3
                    $this->RecSet = $this->SrcId->getIterator();
                }
                $this->RecSet->rewind();
                break;
            case 10: // MySQLi
                $this->RecSet = $this->SrcId->query($Query);
                if ($this->RecSet===false) $this->DataAlert('MySQLi error message when opening the query:'.$this->SrcId->error);
                break;
            case 11: // PDO
                $this->RecSet = $this->SrcId->prepare($Query);
                if ($this->RecSet===false) {
                    $ok = false;
                } else {
                    if (!is_array($QryPrms)) $QryPrms = array();
                    $ok = $this->RecSet->execute($QryPrms);
                }
                if (!$ok) {
                    $err = $this->SrcId->errorInfo();
                    $this->DataAlert('PDO error message when opening the query:'.$err[2]);
                }
                break;
            case 12: // Zend_DB_Adapter
                try {
                    if (!is_array($QryPrms)) $QryPrms = array();
                    $this->RecSet = $this->SrcId->query($Query, $QryPrms);
                } catch (Exception $e) {
                    $this->DataAlert('Zend_DB_Adapter error message when opening the query: '.$e->getMessage());
                }
                break;
        }

        if (($this->Type===0) or ($this->Type===9)) {
            unset($this->RecKey); $this->RecKey = '';
        } else {
            if ($this->RecSaving) {
                unset($this->RecBuffer); $this->RecBuffer = array();
            }
            $this->RecKey = &$this->RecNum; // Not array: RecKey = RecNum
        }

        return ($this->RecSet!==false);

    }

    public function DataFetch() {

        if ($this->RecSaved) {
            if ($this->RecNum<$this->RecNbr) {
                if ($this->FirstRec) {
                    if ($this->SubType===2) { // From string
                        reset($this->RecSet);
                        $this->RecKey = key($this->RecSet);
                        $this->CurrRec = &$this->RecSet[$this->RecKey];
                    } else {
                        $this->CurrRec = reset($this->RecSet);
                        $this->RecKey = key($this->RecSet);
                    }
                    $this->FirstRec = false;
                } else {
                    if ($this->SubType===2) { // From string
                        next($this->RecSet);
                        $this->RecKey = key($this->RecSet);
                        $this->CurrRec = &$this->RecSet[$this->RecKey];
                    } else {
                        $this->CurrRec = next($this->RecSet);
                        $this->RecKey = key($this->RecSet);
                    }
                }
                if ((!is_array($this->CurrRec)) and (!is_object($this->CurrRec))) $this->CurrRec = array('key'=>$this->RecKey, 'val'=>$this->CurrRec);
                $this->RecNum++;
                if ($this->OnDataOk) {
				$this->OnDataArgs[1] = &$this->CurrRec; // Reference has changed if ($this->SubType===2)
				if ($this->OnDataPrm) call_user_func_array($this->OnDataPrmRef,$this->OnDataArgs);
				if ($this->OnDataPi) $this->TBS->meth_PlugIn_RunAll($this->OnDataPiRef,$this->OnDataArgs);
				if ($this->SubType!==2) $this->RecSet[$this->RecKey] = $this->CurrRec; // save modifications because array reading is done without reference :(
			}
            } else {
                unset($this->CurrRec); $this->CurrRec = false;
            }
            return;
        }

        switch ($this->Type) {
            case 6: // MySQL
                $this->CurrRec = mysql_fetch_assoc($this->RecSet);
                break;
            case 1: // Num
                if (($this->NumVal>=$this->NumMin) and ($this->NumVal<=$this->NumMax)) {
                    $this->CurrRec = array('val'=>$this->NumVal);
                    $this->NumVal += $this->NumStep;
                } else {
                    $this->CurrRec = false;
                }
                break;
            case 2: // Text
                if ($this->RecNum===0) {
                    if ($this->RecSet==='') {
                        $this->CurrRec = false;
                    } else {
                        $this->CurrRec = &$this->RecSet;
                    }
                } else {
                    $this->CurrRec = false;
                }
                break;
            case 3: // Custom function
                $FctFetch = $this->FctFetch;
                $this->CurrRec = $FctFetch($this->RecSet,$this->RecNum+1);
                break;
            case 4: // Custom method from ObjectRef
                $this->FctPrm[0] = &$this->RecSet; $this->FctPrm[1] = $this->RecNum+1;
		$this->CurrRec = call_user_func_array($this->FctFetch,$this->FctPrm);
		break;
            case 5: // Custom method of object
                $this->CurrRec = $this->SrcId->tbsdb_fetch($this->RecSet,$this->RecNum+1);
                break;
            case 7: // PostgreSQL
                $this->CurrRec = pg_fetch_assoc($this->RecSet);
                break;
            case 8: // SQLite
                $this->CurrRec = sqlite_fetch_array($this->RecSet,SQLITE_ASSOC);
                break;
            case 9: // Iterator
                if ($this->RecSet->valid()) {
                    $this->CurrRec = $this->RecSet->current();
                    $this->RecKey = $this->RecSet->key();
                    $this->RecSet->next();
                } else {
                    $this->CurrRec = false;
                }
                break;
            case 10: // MySQLi
                $this->CurrRec = $this->RecSet->fetch_assoc();
                if (is_null($this->CurrRec)) $this->CurrRec = false;
                break;
            case 11: // PDO
                $this->CurrRec = $this->RecSet->fetch(PDO::FETCH_ASSOC);
                break;
            case 12: // Zend_DB_Adapater
                $this->CurrRec = $this->RecSet->fetch(Zend_Db::FETCH_ASSOC);
                break;
        }

        // Set the row count
        if ($this->CurrRec!==false) {
            $this->RecNum++;
            if ($this->OnDataOk) {
                if ($this->OnDataPrm) call_user_func_array($this->OnDataPrmRef,$this->OnDataArgs);
                if ($this->OnDataPi) $this->TBS->meth_PlugIn_RunAll($this->OnDataPiRef,$this->OnDataArgs);
            }
            if ($this->RecSaving) $this->RecBuffer[$this->RecKey] = $this->CurrRec;
        }

    }

    public function DataClose() {
        $this->OnDataOk = false;
        $this->OnDataPrm = false;
        $this->OnDataPi = false;
        if ($this->RecSaved) return;
        switch ($this->Type) {
            case 6: mysql_free_result($this->RecSet); break;
            case 3: $FctClose=$this->FctClose; $FctClose($this->RecSet); break;
            case 4: call_user_func_array($this->FctClose,array(&$this->RecSet)); break;
            case 5: $this->SrcId->tbsdb_close($this->RecSet); break;
            case 7: pg_free_result($this->RecSet); break;
            case 10: $this->RecSet->free(); break; // MySQLi
            //case 11: $this->RecSet->closeCursor(); break; // PDO
        }
        if ($this->RecSaving) {
            $this->RecSet = &$this->RecBuffer;
            $this->RecNbr = $this->RecNumInit + count($this->RecSet);
            $this->RecSaving = false;
            $this->RecSaved = true;
        }
    }

}