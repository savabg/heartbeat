<?php
/**
 * Created by JetBrains PhpStorm.
 * User: SaVa
 * Date: 7/3/12
 * Time: 1:47 AM
 * To change this template use File | Settings | File Templates.
 */
namespace TBS;


class clsOpenTBS extends clsTbsZip {

    function OnInstall() {
        $TBS =& $this->TBS;

        if (!isset($TBS->OtbsAutoLoad)) $TBS->OtbsAutoLoad = true; // TBS will load the subfile regarding to the extension of the archive
        if (!isset($TBS->OtbsConvBr))   $TBS->OtbsConvBr = false;  // string for NewLine conversion
        if (!isset($TBS->OtbsAutoUncompress)) $TBS->OtbsAutoUncompress = $this->Meth8Ok;
        if (!isset($TBS->OtbsConvertApostrophes)) $TBS->OtbsConvertApostrophes = true;
        if (!isset($TBS->OtbsSpacePreserve)) $TBS->OtbsSpacePreserve = true;
        $this->Version = '1.7.6';
        $this->DebugLst = false; // deactivate the debug mode
        $this->ExtInfo = false;
        $TBS->TbsZip = &$this; // a shortcut
        return array('BeforeLoadTemplate','BeforeShow', 'OnCommand', 'OnOperation', 'OnCacheField');
    }

    function BeforeLoadTemplate(&$File,&$Charset) {

        $TBS =& $this->TBS;
        if ($TBS->_Mode!=0) return; // If we are in subtemplate mode, the we use the TBS default process

        // Decompose the file path. The syntaxe is 'Archive.ext#subfile', or 'Archive.ext', or '#subfile'
        $p = strpos($File, '#');
        if ($p===false) {
            $FilePath = $File;
            $SubFileLst = false;
        } else {
            $FilePath = substr($File,0,$p);
            $SubFileLst = substr($File,$p+1);
        }

        // Open the archive
        if ($FilePath!=='') {
            $this->Open($FilePath);  // Open the archive
            $this->Ext_PrepareInfo(); // Set extension information
            if ($TBS->OtbsAutoLoad && ($this->ExtInfo!==false) && ($SubFileLst===false)) {
                // auto load files from the archive
                $SubFileLst = $this->ExtInfo['load'];
                $TBS->OtbsConvBr = $this->ExtInfo['br'];
            }
            $TBS->OtbsCurrFile = false;
            $TBS->OtbsSubFileLst = $SubFileLst;
            $this->TbsStoreLst = array();
            $this->TbsCurrIdx = false;
        } elseif ($this->ArchFile==='') {
            $this->RaiseError('Cannot read file(s) "'.$SubFileLst.'" because no archive is opened.');
        }

        // Change the Charset if a new archive is opended, or if LoadTemplate is called explicitely for that
        if (($FilePath!=='') || ($File==='')) {
            if ($Charset===OPENTBS_ALREADY_XML) {
                $TBS->LoadTemplate('', false);                       // Define the function for string conversion
            } elseif ($Charset===OPENTBS_ALREADY_UTF8) {
                $TBS->LoadTemplate('', array(&$this,'ConvXmlOnly')); // Define the function for string conversion
            } else {
                $TBS->LoadTemplate('', array(&$this,'ConvXmlUtf8')); // Define the function for string conversion
            }
        }

        $TbsLoadTemplate = (($TBS->Render & OPENTBS_DEBUG_AVOIDAUTOFIELDS)!=OPENTBS_DEBUG_AVOIDAUTOFIELDS);

        // Load the subfile(s)
        if (($SubFileLst!=='') && ($SubFileLst!==false)) {

            if (is_string($SubFileLst)) $SubFileLst = explode(';',$SubFileLst);

            $ModeSave = $TBS->_Mode;
            $TBS->_Mode++;    // deactivate TplVars[] reset and Charset reset.
            $TBS->Plugin(-4); // deactivate other plugins

            foreach ($SubFileLst as $SubFile) {

                $idx = $this->FileGetIdx($SubFile);
                if ($idx===false) {
                    $this->RaiseError('The file "'.$SubFile.'" is not found in the archive "'.$this->ArchFile.'".');
                } elseif ($idx!==$this->TbsCurrIdx) {
                    // Save the current loaded subfile if any
                    $this->TbsStorePark();
                    // load the subfile
                    $TBS->Source = $this->TbsStoreGet($idx, false);
                    if ($this->LastReadNotStored) {
                        if ($this->LastReadComp<=0) { // the contents is not compressed
                            if ($this->ExtInfo!==false) {
                                $i = $this->ExtInfo;
                                if (isset($i['rpl_what'])) $TBS->Source = str_replace($i['rpl_what'], $i['rpl_with'], $TBS->Source); // auto replace strings in the loaded file
                                if (($i['ext']==='docx') && isset($TBS->OtbsClearMsWord) && $TBS->OtbsClearMsWord) $this->MsWord_Clean($TBS->Source);
                                if (($i['ext']==='xlsx') && isset($TBS->OtbsMsExcelConsistent) && isset($TBS->OtbsMsExcelConsistent) ) {
                                    $this->MsExcel_DeleteFormulaResults($TBS->Source);
                                    $this->MsExcel_ConvertToRelative($TBS->Source);
                                }
                            }
                            // apply default TBS behaviors on the uncompressed content: other plug-ins + [onload] fields
                            if ($TbsLoadTemplate) $TBS->LoadTemplate(null,'+');
                        }
                    }

                    $TBS->OtbsCurrFile = $SubFile;
                    $this->TbsCurrIdx = $idx;

                }

            }

            // Reactivate default configuration
            $TBS->_Mode = $ModeSave;
            $TBS->Plugin(-10); // reactivate other plugins

        }

        if ($FilePath!=='') $TBS->_LastFile = $FilePath;

        return false; // default LoadTemplate() process is not executed

    }

    function BeforeShow(&$Render, $File='') {

        $TBS =& $this->TBS;

        if ($TBS->_Mode!=0) return; // If we are in subtemplate mode, the we use the TBS default process

        $this->TbsStorePark(); // Save the current loaded subfile if any

        $TBS->Plugin(-4); // deactivate other plugins

        $Debug = (($Render & OPENTBS_DEBUG_XML)==OPENTBS_DEBUG_XML);
        if ($Debug) $this->DebugLst = array();

        $TbsShow = (($Render & OPENTBS_DEBUG_AVOIDAUTOFIELDS)!=OPENTBS_DEBUG_AVOIDAUTOFIELDS);

        if (isset($this->OtbsSheetODS))   $this->OpenDoc_SheetDeleteAndDisplay();
        if (isset($this->OtbsSheetXLSX))  $this->MsExcel_SheetDeleteAndDisplay();
        if ($this->Ext_Get()=='docx')  $this->MsWord_RenumDocPr();

        // Merges all modified subfiles
        $idx_lst = array_keys($this->TbsStoreLst);
        foreach ($idx_lst as $idx) {
            $TBS->Source = $this->TbsStoreLst[$idx]['src'];
            $onshow = $this->TbsStoreLst[$idx]['onshow'];
            unset($this->TbsStoreLst[$idx]); // save memory space
            $TBS->OtbsCurrFile = $this->TbsGetFileName($idx); // usefull for TbsPicAdd()
            $this->TbsCurrIdx = $idx; // usefull for debug mode
            if ($TbsShow && $onshow) $TBS->Show(TBS_NOTHING);
            if ($Debug) $this->DebugLst[$this->TbsGetFileName($idx)] = $TBS->Source;
            $this->FileReplace($idx, $TBS->Source, TBSZIP_STRING, $TBS->OtbsAutoUncompress);
        }
        $TBS->Plugin(-10); // reactivate other plugins
        $this->TbsCurrIdx = false;

        if (isset($this->OpenXmlRid))    $this->OpenXML_RidCommit($Debug);       // Commit special OpenXML features if any
        if (isset($this->OpenXmlCTypes)) $this->OpenXML_CTypesCommit($Debug);    // Commit special OpenXML features if any
        if (isset($this->OpenDocManif))  $this->OpenDoc_ManifestCommit($Debug);  // Commit special OpenDocument features if any

        if ( ($TBS->ErrCount>0) && (!$TBS->NoErr) && (!$Debug)) {
            $TBS->meth_Misc_Alert('Show() Method', 'The output is cancelled by the OpenTBS plugin because at least one error has occured.');
            exit;
        }

        if ($Debug) {
            // Do the debug even if other options are used
            $this->TbsDebug_Merge(true, false);
        } elseif (($Render & TBS_OUTPUT)==TBS_OUTPUT) { // notice that TBS_OUTPUT = OPENTBS_DOWNLOAD
            // download
            $ContentType = (isset($this->ExtInfo['ctype'])) ? $this->ExtInfo['ctype'] : '';
            $this->Flush($Render, $File, $ContentType); // $Render is used because it can contain options OPENTBS_DOWNLOAD and OPENTBS_NOHEADER.
            $Render = $Render - TBS_OUTPUT; //prevent TBS from an extra output.
        } elseif(($Render & OPENTBS_FILE)==OPENTBS_FILE) {
            // to file
            $this->Flush(TBSZIP_FILE, $File);
        } elseif(($Render & OPENTBS_STRING)==OPENTBS_STRING) {
            // to string
            $this->Flush(TBSZIP_STRING);
            $TBS->Source = $this->OutputSrc;
            $this->OutputSrc = '';
        }

        if (($Render & TBS_EXIT)==TBS_EXIT) {
            $this->Close();
            exit;
        }

        return false; // cancel the default Show() process

    }

    function OnCacheField($BlockName,&$Loc,&$Txt,$PrmProc) {

        if (isset($Loc->PrmLst['ope'])) {

            // Prepare to change picture
            $ope_lst = explode(',', $Loc->PrmLst['ope']); // in this event, ope is not exploded
            if (in_array('changepic', $ope_lst)) {
                $this->TbsPicFound($Txt, $Loc); // add parameter "att" which will be processed just after this event, when the field is cached
                $Loc->PrmLst['pic_change'] = true;
            }

            // Change cell type in ODS files
            if (strpos(','.$Loc->PrmLst['ope'],',ods')!==false) {
                foreach($ope_lst as $ope) {
                    if (substr($ope,0,3)==='ods') {
                        $x = '';
                        $this->OpenDoc_ChangeCellType($Txt, $Loc, $ope, false, $x);
                        return; // do it only once
                    }
                }
            }

            // Change cell type in XLSX files
            if (strpos(','.$Loc->PrmLst['ope'],',xlsx')!==false) {
                foreach($ope_lst as $ope) {
                    if (substr($ope,0,4)==='xlsx') {
                        $x = '';
                        $this->MsExcel_ChangeCellType($Txt, $Loc, $ope);
                        return; // do it only once
                    }
                }
            }

        }

    }

    function OnOperation($FieldName,&$Value,&$PrmLst,&$Txt,$PosBeg,$PosEnd,&$Loc) {
        // in this event, ope is exploded, there is one function call for each ope command
        $ope = $PrmLst['ope'];
        if ($ope==='addpic') {
            $this->TbsPicAdd($Value, $PrmLst, $Txt, $Loc, 'ope=addpic');
        } elseif ($ope==='changepic') {
            if (!isset($PrmLst['pic_change'])) {
                $this->TbsPicFound($Txt, $Loc);  // add parameter "att" which will be processed just before the value is merged
                $PrmLst['pic_change'] = true;
            }
            $this->TbsPicAdd($Value, $PrmLst, $Txt, $Loc, 'ope=changepic');
        } elseif(substr($ope,0,4)==='xlsx') {
            if (!isset($Loc->PrmLst['xlsxok'])) $this->MsExcel_ChangeCellType($Txt, $Loc, $ope);
            switch ($Loc->PrmLst['xlsxok']) {
                case 'xlsxNum':
                    if (is_numeric($Value)) {
                        // we have to check contents in order to avoid Excel errors. Note that value '0.00000000000000' makes an Excel error.
                        if (strpos($Value,'e')!==false) { // exponential representation
                            $Value = (float) $Value;
                        } elseif (strpos($Value,'x')!==false) { // hexa representation
                            $Value = hexdec($Value);
                        } elseif (strpos($Value,'.')===false) {
                            $Value = (integer) $Value;
                        } else {
                            $Value = (float) $Value;
                        }
                    } else {
                        $Value = '';
                    }
                    $Value = (is_numeric($Value)) ? ''.$Value : '';
                    break;
                case 'xlsxBool':
                    $Value = ($Value) ? 1 : 0;
                    break;
                case 'xlsxDate':
                    if (is_string($Value)) {
                        $t = strtotime($Value); // We look if it's a date
                    } else {
                        $t = $Value;
                    }
                    if (($t===-1) or ($t===false)) { // Date not recognized
                        $Value = '';
                    } elseif ($t===943916400) { // Date to zero
                        $Value = '';
                    } else { // It's a date
                        $Value = ($t/86400.00)+25569; // unix: 1 means 01/01/1970, xls: 1 means 01/01/1900
                    }
                    break;
                default:
                    // do nothing
            }
        } elseif(substr($ope,0,3)==='ods') {
            if (!isset($Loc->PrmLst['odsok'])) $this->OpenDoc_ChangeCellType($Txt, $Loc, $ope, true, $Value);
        }
    }

    function OnCommand($Cmd, $x1=null, $x2=null, $x3=null, $x4=null, $x5=null) {

        if ($Cmd==OPENTBS_INFO) {

            // Display debug information
            echo "<strong>OpenTBS plugin Information</strong><br>\r\n";
            return $this->Debug();

        } elseif ($Cmd==OPENTBS_RESET) {

            // Reset all mergings
            $this->ArchCancelModif();
            $this->TbsStoreLst = array();
            $TBS =& $this->TBS;
            $TBS->Source = '';
            $TBS->OtbsCurrFile = false;
            if (is_string($TBS->OtbsSubFileLst)) {
                $f = '#'.$TBS->OtbsSubFileLst;
                $h = '';
                $this->BeforeLoadTemplate($f,$h);
            }
            return true;

        } elseif ( ($Cmd==OPENTBS_ADDFILE) || ($Cmd==OPENTBS_REPLACEFILE) ) {

            // Add a new file or cancel a previous add
            $Name = (is_null($x1)) ? false : $x1;
            $Data = (is_null($x2)) ? false : $x2;
            $DataType = (is_null($x3)) ? TBSZIP_STRING : $x3;
            $Compress = (is_null($x4)) ? true : $x4;

            if ($Cmd==OPENTBS_ADDFILE) {
                return $this->FileAdd($Name, $Data, $DataType, $Compress);
            } else {
                return $this->FileReplace($Name, $Data, $DataType, $Compress);
            }

        } elseif ($Cmd==OPENTBS_DELETEFILE) {

            // Delete an existing file in the archive
            $Name = (is_null($x1)) ? false : $x1;
            $this->FileCancelModif($Name, false);    // cancel added files
            return $this->FileReplace($Name, false); // mark the file as to be deleted

        } elseif ($Cmd==OPENTBS_FILEEXISTS) {

            return $this->FileExists($x1);

        } elseif ($Cmd==OPENTBS_CHART) {

            $ChartNameOrNum = $x1;
            $SeriesNameOrNum = $x2;
            $NewValues = (is_null($x3)) ? false : $x3;
            $NewLegend = (is_null($x4)) ? false : $x4;
            $CopyFromSeries = (is_null($x5)) ? false : $x5;
            return $this->OpenXML_ChartChangeSeries($ChartNameOrNum, $SeriesNameOrNum, $NewValues, $NewLegend, $CopyFromSeries);

        } elseif ( ($Cmd==OPENTBS_DEBUG_INFO) || ($Cmd==OPENTBS_DEBUG_CHART_LIST) ) {

            if (is_null($x1)) $x1 = true;
            $this->TbsDebug_Info($x1);

        } elseif ($Cmd==OPENTBS_DEBUG_XML_SHOW) {

            $this->TBS->Show(OPENTBS_DEBUG_XML);

        } elseif ($Cmd==OPENTBS_DEBUG_XML_CURRENT) {

            $this->TbsStorePark();
            $this->DebugLst = array();
            foreach ($this->TbsStoreLst as $idx=>$park) $this->DebugLst[$this->TbsGetFileName($idx)] = $park['src'];
            $this->TbsDebug_Merge(true, true);

        } elseif($Cmd==OPENTBS_FORCE_DOCTYPE) {

            return $this->Ext_PrepareInfo($x1);

        } elseif ($Cmd==OPENTBS_DELETE_ELEMENTS) {

            if (is_string($x1)) $x1 = explode(',', $x1);
            if (is_null($x2)) $x2 = false; // OnlyInner
            return $this->XML_DeleteElements($this->TBS->Source, $x1, $x2);

        } elseif ($Cmd==OPENTBS_SELECT_MAIN) {

            if ( ($this->ExtInfo!==false) && isset($this->ExtInfo['main']) ) {
                $this->TBS->LoadTemplate('#'.$this->ExtInfo['main']);
                return true;
            } else {
                return false;
            }

        } elseif ($Cmd==OPENTBS_SELECT_SHEET) {

            // Only XLSX files have sheets in separated subfiles.
            if ($this->Ext_Get()==='xlsx') {
                $loc = $this->MsExcel_SheetGet($x1, $Cmd, true);
                if ($loc==false) return;
                if ($this->FileExists($loc->xlsxTarget)) {
                    $this->TBS->LoadTemplate('#'.$loc->xlsxTarget);
                } else {
                    return $this->RaiseError("($Cmd) sub-file '".$loc->xlsxTarget."' is not found inside the Workbook.");
                }
            }
            return true;

        } elseif ( ($Cmd==OPENTBS_DELETE_SHEETS) || ($Cmd==OPENTBS_DISPLAY_SHEETS) ) {

            if (is_null($x2)) $x2 = true; // default value
            $delete = ($Cmd==OPENTBS_DELETE_SHEETS);

            $ext = $this->Ext_Get();
            if (!isset($this->OtbsSheetOk)) {
                if ($ext=='xlsx') $this->OtbsSheetXLSX = true;
                if ($ext=='ods') $this->OtbsSheetODS = true;
                $this->OtbsSheetDelete = array();
                $this->OtbsSheetVisible = array();
                $this->OtbsSheetOk = true;
            }

            $x2 = (boolean) $x2;
            if (!is_array($x1)) $x1 = array($x1);

            foreach ($x1 as $sheet=>$action) {
                if (!is_bool($action)) {
                    $sheet = $action;
                    $action = $x2;
                }
                $sheet_ref = (is_string($sheet)) ? 'n:'.htmlspecialchars($sheet) : 'i:'.$sheet; // help to make the difference beetween id and name
                if ($delete) {
                    if ($x2) {
                        $this->OtbsSheetDelete[$sheet_ref] = $sheet;
                    } else {
                        unset($this->OtbsSheetDelete[$sheet_ref]);
                    }
                } else {
                    $this->OtbsSheetVisible[$sheet_ref] = $x2;
                }
            }

        } elseif ($Cmd==OPENTBS_DELETE_COMMENTS) {

            // Default values
            $MainTags = false;
            $CommFiles = false;
            $CommTags = false;
            $Inner = false;

            if ($this->Ext_GetFrm()=='odf') {
                $MainTags = array('office:annotation', 'officeooo:annotation'); // officeooo:annotation is used in ODP Presentations
            } else {
                switch ($this->Ext_Get()) {
                    case 'docx':
                        $MainTags = array('w:commentRangeStart', 'w:commentRangeEnd', 'w:commentReference');
                        $CommFiles = array('wordprocessingml.comments+xml');
                        $CommTags = array('w:comment');
                        $Inner = true;
                        break;
                    case 'xlsx':
                        $CommFiles = array('spreadsheetml.comments+xml');
                        $CommTags = array('comment', 'author');
                        break;
                    case 'pptx':
                        $CommFiles = array('presentationml.comments+xml');
                        $CommTags = array('p:cm');
                        break;
                    default:
                        return 0;
                }
            }

            return $this->TbsDeleteComments($MainTags, $CommFiles, $CommTags, $Inner);

        }

    }

    function TbsStorePark() {
        // save the last opened subfile into the store, and close the subfile
        if ($this->TbsCurrIdx!==false) {
            $this->TbsStoreLst[$this->TbsCurrIdx] = array('src'=>$this->TBS->Source, 'onshow'=>true);
            $this->TBS->Source = '';
            $this->TbsCurrIdx = false;
        }
    }

    function TbsStorePut($idx, $src, $onshow = null) {
        // Save a given source in the store. If $onshow is null, then it stay unchanged
        if ($idx===$this->TbsCurrIdx) {
            $this->TBS->Source = $src;
        } else {
            if (is_null($onshow)) {
                if (isset($this->TbsStoreLst[$idx])) {
                    $onshow = $this->TbsStoreLst[$idx]['onshow'];
                } else {
                    $onshow = false;
                }
            }
            $this->TbsStoreLst[$idx] = array('src'=>$src, 'onshow'=>$onshow);
        }
    }

    function TbsStoreGet($idx, $caller) {
        // retrieve a source from the merging, the store, or the archive
        // the file is not stored yet if it comes from the archive
        $this->LastReadNotStored = false;
        if ($idx===$this->TbsCurrIdx) {
            return $this->TBS->Source;
        } elseif (isset($this->TbsStoreLst[$idx])) {
            return $this->TbsStoreLst[$idx]['src'];
        } else {
            $this->LastReadNotStored = true;
            $txt = $this->FileRead($idx, true);
            if ($this->LastReadComp>0) {
                if ($caller===false) {
                    return $txt; // return the uncompressed contents
                } else {
                    return $this->RaiseError("(".$caller.") unable to uncompress '".$this->TbsGetFileName($idx)."'.");
                }
            } else {
                return $txt;
            }
        }
    }

    function TbsGetFileName($idx) {
        if (isset($this->CdFileLst[$idx])) {
            return $this->CdFileLst[$idx]['v_name'];
        } else {
            return '(id='.$idx.')';
        }
    }

    function TbsDebug_Init(&$nl, &$sep, &$bull, $type) {
        // display the header of debug mode

        $nl = "\n";
        $sep = str_repeat('-',30);
        $bull = $nl.'  - ';

        if (isset($this->DebugInit)) return;
        $this->DebugInit = true;

        if (!headers_sent()) header('Content-Type: text/plain; charset="UTF-8"');

        echo "* OPENTBS DEBUG MODE: if the star, (*) on the left before the word OPENTBS, is not the very first character of this page, then your
merged Document will be corrupted when you use the OPENTBS_DOWNLOAD option. If there is a PHP error message, then you have to fix it.
If they are blank spaces, line beaks, or other unexpected characters, then you have to check your code in order to avoid them.";
        echo $nl;
        echo $nl.$sep.$nl.'INFORMATION'.$nl.$sep;
        echo $nl.'* Debug command: '.$type;
        echo $nl.'* OpenTBS version: '.$this->Version;
        echo $nl.'* TinyButStrong version: '.$this->TBS->Version;
        echo $nl.'* PHP version: '.PHP_VERSION;
        echo $nl.'* Opened document: '.$this->ArchFile;
        echo $nl.'* Activated features for document type: '.(($this->ExtInfo===false) ? '(none)' : $this->ExtInfo['frm'].'/'.$this->ExtInfo['ext']);

    }

    function TbsDebug_Info($Exit) {

        $this->TbsDebug_Init($nl, $sep, $bull, 'OPENTBS_DEBUG_INFO');

        if ($this->Ext_Get()==='xlsx') $this->MsExcel_SheetDebug($nl, $sep, $bull);
        if ($this->Ext_Get()==='ods')  $this->OpenDoc_SheetDebug($nl, $sep, $bull);


        if ($this->Ext_GetFrm()==='openxml') {
            $this->OpenXML_ChartDebug($nl, $sep, $bull);
        }

        if ($Exit) exit;

    }

    function TbsDebug_Merge($XmlFormat = true, $Current) {
        // display modified and added files

        $this->TbsDebug_Init($nl, $sep, $bull, ($Current ? 'OPENTBS_DEBUG_XML_CURRENT' :'OPENTBS_DEBUG_XML_SHOW'));

        // scann files for collecting information
        $mod_lst = ''; // id of modified files
        $del_lst = ''; // id of deleted  files
        $add_lst = ''; // id of added    files

        // files marked as replaced in TbsZip
        $idx_lst = array_keys($this->ReplInfo);
        foreach ($idx_lst as $idx) {
            $name = $this->TbsGetFileName($idx);
            if ($this->ReplInfo[$idx]===false) {
                $del_lst .= $bull.$name;
            } else {
                $mod_lst .= $bull.$name;
            }
        }

        // files marked as modified in the Park
        $idx_lst = array_keys($this->TbsStoreLst);
        foreach ($idx_lst as $idx) {
            if (!isset($this->ReplInfo[$idx])) {
                $mod_lst .= $bull.$this->TbsGetFileName($idx);
            }
        }

        // files marked as added in TbsZip
        $idx_lst = array_keys($this->AddInfo);
        foreach ($idx_lst as $idx) {
            $name = $this->AddInfo[$idx]['name'];
            $add_lst .= $bull.$name;
        }

        if ($mod_lst==='')  $mod_lst = ' none';
        if ($del_lst==='')  $del_lst = ' none';
        if ($add_lst==='')  $add_lst = ' none';

        echo $nl.'* Deleted files in the archive:'.$del_lst;
        echo $nl.'* Added files in the archive:'.$add_lst;
        echo $nl.'* Modified files in the archive:'.$mod_lst;
        echo $nl;

        // display contents merged with OpenTBS
        foreach ($this->DebugLst as $name=>$src) {
            $x = trim($src);
            $info = '';
            $xml = ((strlen($x)>0) && $x[0]==='<');
            if ($XmlFormat && $xml) {
                $info = ' (XML reformated for debuging only)';
                $src = $this->XmlFormat($src);
            }
            echo $nl.$sep;
            echo $nl.'File merged with OpenTBS'.$info.': '.$name;
            echo $nl.$sep;
            echo $nl.$src;
        }

    }

    function ConvXmlOnly($Txt, $ConvBr) {
        // Used by TBS to convert special chars and new lines.
        $x = htmlspecialchars($Txt);
        if ($ConvBr) $this->ConvBr($x);
        return $x;
    }

    function ConvXmlUtf8($Txt, $ConvBr) {
        // Used by TBS to convert special chars and new lines.
        $x = htmlspecialchars(utf8_encode($Txt));
        if ($ConvBr) $this->ConvBr($x);
        return $x;
    }

    function ConvBr(&$x) {
        $z = $this->TBS->OtbsConvBr;
        if ($z===false) return;
        $x = nl2br($x); // Convert any type of line break
        $x = str_replace("\r", '' ,$x);
        $x = str_replace("\n", '' ,$x);
        $x = str_replace('<br />',$z ,$x);

    }

    function XmlFormat($Txt) {
        // format an XML source the be nicely aligned

        // delete line breaks
        $Txt = str_replace("\r",'',$Txt);
        $Txt = str_replace("\n",'',$Txt);

        // init values
        $p = 0;
        $lev = 0;
        $Res = '';

        $to = true;
        while ($to!==false) {
            $to = strpos($Txt,'<',$p);
            if ($to!==false) {
                $tc = strpos($Txt,'>',$to);
                if ($to===false) {
                    $to = false; // anomaly
                } else {
                    // get text between the tags
                    $x = trim(substr($Txt, $p, $to-$p),' ');
                    if ($x!=='') $Res .= "\n".str_repeat(' ',max($lev,0)).$x;
                    // get the tag
                    $x = substr($Txt, $to, $tc-$to+1);
                    if ($Txt[$to+1]==='/') $lev--;
                    $Res .= "\n".str_repeat(' ',max($lev,0)).$x;
                    // change the level
                    if (($Txt[$to+1]!=='?') && ($Txt[$to+1]!=='/') && ($Txt[$tc-1]!=='/')) $lev++;
                    // next position
                    $p = $tc + 1;
                }
            }
        }

        $Res = substr($Res, 1); // delete the first line break
        if ($p<strlen($Txt)) $Res .= trim(substr($Txt, $p), ' '); // complete the end

        return $Res;

    }

    function RaiseError($Msg, $NoErrMsg=false) {
        // Overwrite the parent RaiseError() method.
        $exit = (!$this->TBS->NoErr);
        if ($exit) $Msg .= ' The process is ending, unless you set NoErr property to true.';
        $this->TBS->meth_Misc_Alert('OpenTBS Plugin', $Msg, $NoErrMsg);
        if ($exit) {
            if ($this->DebugLst!==false) {
                if ($this->TbsCurrIdx!==false) $this->DebugLst[$this->TbsGetFileName($this->TbsCurrIdx)] = $this->TBS->Source;
                $this->TbsDebug_Merge(true, false);
            }
            exit;
        }
        return false;
    }

    function TbsPicFound($Txt, &$Loc) {
        // Found the relevent attribute for the image source, and then add parameter 'att' to the TBS locator.
        $att = false;
        if (isset($this->ExtInfo['frm'])) {
            if ($this->ExtInfo['frm']==='odf') {
                $att = 'draw:image#xlink:href';
                if (isset($Loc->PrmLst['adjust'])) $Loc->otbsDim = $this->TbsPicGetDim_ODF($Txt, $Loc->PosBeg);
            } elseif ($this->ExtInfo['frm']==='openxml') {
                $att = $this->OpenXML_FirstPicAtt($Txt, $Loc->PosBeg, true);
                if ($att===false) return $this->RaiseError('Parameter ope=changepic used in the field ['.$Loc->FullName.'] has failed to found the picture.');
                if (isset($Loc->PrmLst['adjust'])) {
                    if (strpos($att,'v:imagedata')!==false) {
                        $Loc->otbsDim = $this->TbsPicGetDim_OpenXML_vml($Txt, $Loc->PosBeg);
                    } else {
                        $Loc->otbsDim = $this->TbsPicGetDim_OpenXML_dml($Txt, $Loc->PosBeg);
                    }
                }
            }
        } else {
            return $this->RaiseError('Parameter ope=changepic used in the field ['.$Loc->FullName.'] is not supported with the current document type.');
        }

        if ($att!==false) {
            if (isset($Loc->PrmLst['att'])) {
                return $this->RaiseError('Parameter att is used with parameter ope=changepic in the field ['.$Loc->FullName.']. changepic will be ignored');
            } else {
                $Loc->PrmLst['att'] = $att;
            }
        }

        return true;

    }

    function TbsPicAdjust(&$Txt, &$Loc, &$File) {
        // Adjust the dimensions if the picture
        $fDim = @getimagesize($File); // file dimensions
        if (!is_array($fDim)) return;
        $w = (float) $fDim[0];
        $h = (float) $fDim[1];
        $r = ($w/$h);
        $delta = 0;
        $adjust = $Loc->PrmLst['adjust'];
        if ( (!is_string($adjust)) || ($adjust=='') ) $adjust = 'inside';
        if (strpos($adjust, '%')!==false) {
            $adjust_coef = floatval(str_replace('%','',$adjust))/100.0;
            $adjust = '%';
        }
        foreach ($Loc->otbsDim as $tDim) { // template dimensions. They must be sorted in reverse order of location
            if ($tDim!==false) {
                // find what dimensions should be edited
                if ($adjust=='%') {
                    if ($tDim['wb']>$tDim['hb']) { // the last attribute must be processed first
                        $edit_lst = array('w' =>  $adjust_coef * $w, 'h' =>  $adjust_coef * $h );
                    } else {
                        $edit_lst = array('h' =>  $adjust_coef * $h, 'w' =>  $adjust_coef * $w );
                    }
                } elseif ($adjust=='samewidth') {
                    $edit_lst = array('h' => $tDim['wv'] * $h / $w );
                } elseif ($adjust=='sameheight') {
                    $edit_lst = array('w' =>  $r * $tDim['hv'] );
                } else { // default value
                    if ($tDim['r']>=$r) {
                        $edit_lst = array('w' =>  $r * $tDim['hv'] ); // adjust width
                    } else {
                        $edit_lst = array('h' => $tDim['wv'] * $h / $w ); // adjust height
                    }
                }
                // edit dimensions
                foreach ($edit_lst as $what=>$new) {
                    $beg  = $tDim[$what.'b'];
                    $len  = $tDim[$what.'l'];
                    $unit = $tDim[$what.'u'];
                    if ($adjust=='%') {
                        if ($tDim['cpt']!==false) $new = $new * $tDim['cpt']; // apply the coef to Point conversion if any
                        if ($unit!=='') { // force unit to pt, if units are allowed
                            $unit = 'pt';
                        }
                    }
                    $new = number_format($new, $tDim['dec'], '.', '').$unit;
                    $Txt = substr_replace($Txt, $new, $beg, $len);
                    if ($Loc->PosBeg>$beg) $delta = $delta + strlen($new) - $len;
                }
            }
        }
        if ($delta<>0) {
            $Loc->PosBeg = $Loc->PosBeg + $delta;
            $Loc->PosEnd = $Loc->PosEnd + $delta;
        }
    }

    function TbsPicGetDim_ODF($Txt, $Pos) {
        // Found the attributes for the image dimensions, in an ODF file
        // unit (can be: mm, cm, in, pi, pt)
        $dim = $this->TbsPicGetDim_Any($Txt, $Pos, 'draw:frame', 'svg:width="', 'svg:height="', 3, false);
        return array($dim);
    }

    function TbsPicGetDim_OpenXML_vml($Txt, $Pos) {
        $dim = $this->TbsPicGetDim_Any($Txt, $Pos, 'v:shape', 'width:', 'height:', 2, false);
        return array($dim);
    }

    function TbsPicGetDim_OpenXML_dml($Txt, $Pos) {
        $dim_shape = $this->TbsPicGetDim_Any($Txt, $Pos, 'wp:extent', 'cx="', 'cy="', 0, 12700);
        $dim_inner = $this->TbsPicGetDim_Any($Txt, $Pos, 'a:ext'    , 'cx="', 'cy="', 0, 12700);
        if ( ($dim_inner!==false) && ($dim_inner['wb']<$dim_shape['wb']) ) $dim_inner = false; // <a:ext> isoptional but must always be after the corresponding <wp:extent>, otherwise it may be the <a:ext> of another picture
        return array($dim_inner, $dim_shape); // dims must be soerted in reverse order of location
    }

    function TbsPicGetDim_Any($Txt, $Pos, $Element, $AttW, $AttH, $AllowedDec, $CoefToPt) {
        // Found the attributes for the image dimensions, in an ODF file
        $p = clsTinyButStrong::f_Xml_FindTagStart($Txt, $Element, true, $Pos, false, true);
        if ($p===false) return false;
        $pe = strpos($Txt, '>', $p);
        if ($pe===false) return false;
        $x = substr($Txt, $p, $pe -$p);
        $att_lst = array('w'=>$AttW, 'h'=>$AttH);
        $res_lst = array();
        foreach ($att_lst as $i=>$att) {
            $l = strlen($att);
            $b = strpos($x, $att);
            if ($b===false) return false;
            $b = $b + $l;
            $e = strpos($x, '"', $b);
            $e2 = strpos($x, ';', $b); // in case of VML format, width and height are styles separted by ;
            if ($e2!==false) $e = min($e, $e2);
            if ($e===false) return false;
            $lt = $e - $b;
            $t = substr($x, $b, $lt);
            $pu = $lt; // unit first char
            while ( ($pu>1) && (!is_numeric($t[$pu-1])) ) $pu--;
            $u = ($pu>=$lt) ? '' : substr($t, $pu);
            $v = floatval(substr($t, 0, $pu));
            $res_lst[$i.'b'] = ($p+$b); // start
            $res_lst[$i.'l'] = $lt; // length of the text
            $res_lst[$i.'u'] = $u; // unit
            $res_lst[$i.'v'] = $v; // value
            $res_lst[$i.'t'] = $t; // text
        }

        $res_lst['r'] = ($res_lst['hv']==0) ? 0.0 : $res_lst['wv']/$res_lst['hv']; // ratio W/H
        $res_lst['dec'] = $AllowedDec; // save the allowed decimal for this attribute
        $res_lst['cpt'] = $CoefToPt;

        return $res_lst;

    }

    function TbsPicAdd(&$Value, &$PrmLst, &$Txt, &$Loc, $Prm) {
        // Add a picture inside the archive, use parameters 'from' and 'as'.
        // Argument $Prm is only used for error messages.

        $TBS = &$this->TBS;

        // set the path where files should be taken
        if (isset($PrmLst['from'])) {
            if (!isset($PrmLst['pic_prepared'])) $TBS->meth_Merge_AutoVar($PrmLst['from'],true); // merge automatic TBS fields in the path
            $FullPath = str_replace($TBS->_ChrVal,$Value,$PrmLst['from']); // merge [val] fields in the path
        } else {
            $FullPath = $Value;
        }
        if ( (!isset($PrmLst['pic_prepared'])) && isset($PrmLst['default']) ) $TBS->meth_Merge_AutoVar($PrmLst['default'],true); // merge automatic TBS fields in the path

        $ok = true; // true if the picture file is actually inserted and ready to be changed

        // check if the picture exists, and eventually use the default picture
        if (!file_exists($FullPath)) {
            if (isset($PrmLst['default'])) {
                $x = $PrmLst['default'];
                if ($x==='current') {
                    $ok = false;
                } elseif (file_exists($x)) {
                    $FullPath = $x;
                } else {
                    $ok = $this->RaiseError('The default picture "'.$x.'" defined by parameter "default" of the field ['.$Loc->FullName.'] is not found.');
                }
            } else {
                $ok = $this->RaiseError('The picture "'.$FullPath.'" that is supposed to be added because of parameter "'.$Prm.'" of the field ['.$Loc->FullName.'] is not found. You can use parameter default=current to cancel this message');
            }
        }

        // set the name of the internal file
        if (isset($PrmLst['as'])) {
            if (!isset($PrmLst['pic_prepared'])) $TBS->meth_Merge_AutoVar($PrmLst['as'],true); // merge automatic TBS fields in the path
            $InternalPath = str_replace($TBS->_ChrVal,$Value,$PrmLst['as']); // merge [val] fields in the path
        } else {
            $InternalPath = basename($FullPath);
        }

        if ($ok) {

            // the value of the current TBS field becomes the full internal path
            if (isset($this->ExtInfo['pic_path'])) $InternalPath = $this->ExtInfo['pic_path'].$InternalPath;

            // actually add the picture inside the archive
            if ($this->FileGetIdxAdd($InternalPath)===false) $this->FileAdd($InternalPath, $FullPath, TBSZIP_FILE, true);

            // preparation for others file in the archive
            $Rid = false;
            $Frm = $this->Ext_GetFrm();
            if ($Frm==='odf') {
                // OpenOffice document
                $this->OpenDoc_ManifestChange($InternalPath,'');
            } elseif ($Frm==='openxml') {
                // Microsoft Office document
                $this->OpenXML_CTypesPrepare($InternalPath, '');
                $Rid = $this->OpenXml_RidPrepare($TBS->OtbsCurrFile, basename($InternalPath));
            }

            // change the value of the field for the merging process
            if ($Rid===false) {
                $Value = $InternalPath;
            } else {
                $Value = $Rid; // the Rid is used instead of the file name for the merging
            }

        }

        // Change the dimensions of the picture
        if (isset($Loc->otbsDim)) {
            if (isset($Loc->AttForward)) { // the field has been already moved by parameter att
                if (!isset($Loc->otbsRealBeg)) { // save the real position of the field
                    $Loc->otbsRealBeg = $Loc->PosBeg;
                    $Loc->otbsRealEnd = $Loc->PosEnd;
                } else { // restore the real position of the field
                    $Loc->PosBeg = $Loc->otbsRealBeg;
                    $Loc->PosEnd = $Loc->otbsRealEnd;
                }
            }
            if ($ok) $this->TbsPicAdjust($Txt, $Loc, $FullPath);
        }

        // Unchanged value (must be done after redim)
        if (!$ok) $Value = substr($Txt, $Loc->PosBeg, $Loc->PosEnd - $Loc->PosBeg + 1);

        $PrmLst['pic_prepared'] = true; // mark the locator as Picture prepared

        return $ok;

    }

    // Check after the sheet process
    function TbsSheetCheck() {
        if (count($this->OtbsSheetDelete)>0) $this->RaiseError("Unable to delete the following sheets because they are not found in the workbook: ".(str_replace(array('i:','n:'),'',implode(', ',$this->OtbsSheetDelete))).'.');
        if (count($this->OtbsSheetVisible)>0) $this->RaiseError("Unable to change visibility of the following sheets because they are not found in the workbook: ".(str_replace(array('i:','n:'),'',implode(', ',array_keys($this->OtbsSheetVisible)))).'.');
    }

    function TbsDeleteComments($MainTags, $CommFiles, $CommTags, $Inner) {

        $nbr = 0;

        // Retrieve the Comment sub-file (OpenXML only)
        if ($CommFiles!==false) {
            $Files = $this->OpenXML_MapGetFiles($CommFiles);
            foreach ($Files as $file) {
                $idx = $this->FileGetIdx($file);
                if ($idx!==false) {
                    // Delete inner text of the comments to be sure that contents is deleted
                    // we only empty the comment elements in case some comments are referenced in other special part of the document
                    $Txt = $this->TbsStoreGet($idx, "Delete Comments");
                    $nbr = $nbr + $this->XML_DeleteElements($Txt, $CommTags, $Inner);
                    $this->TbsStorePut($idx, $Txt);
                }
            }
        }

        // Retrieve the Main sub-file
        if ($MainTags!==false) {
            $idx = $this->FileGetIdx($this->ExtInfo['main']);
            if ($idx===false) return false;
            // Delete Comment locators
            $Txt = $this->TbsStoreGet($idx, "Delete Comments");
            $nbr2 = $this->XML_DeleteElements($Txt, $MainTags);
            $this->TbsStorePut($idx, $Txt);
            if ($CommFiles===false) $nbr = $nbr2;
        }

        return $nbr;

    }

    function Ext_PrepareInfo($Ext=false) {
        /* Extension Info must be an array with keys 'load', 'br', 'ctype' and 'pic_path'. Keys 'rpl_what' and 'rpl_with' are optional.
            load:     files in the archive to be automatically loaded by OpenTBS when the archive is loaded. Separate files with comma ';'.
            br:       string that replace break-lines in the values merged by TBS, set to false if no conversion.
            frm:      format of the file ('odf' or 'openxml'), for now it is used only to activate a special feature for openxml files
            ctype:    (optional) the Content-Type header name that should be use for HTTP download. Omit or set to '' if not specified.
            pic_path: (optional) the folder nale in the archive where to place pictures
            rpl_what: (optional) string to replace automatically in the files when they are loaded. Can be a string or an array.
            rpl_with: (optional) to be used with 'rpl_what',  Can be a string or an array.

            User can define his own Extension Information, they are taken in acount if saved int the global variable $_OPENTBS_AutoExt.
           */

        if ($Ext===false) {
            // Get the extension of the current archive
            $Ext = basename($this->ArchFile);
            $p = strrpos($Ext, '.');
            $Ext = ($p===false) ? '' : strtolower(substr($Ext, $p + 1));
            $Frm = $this->Ext_GetFormat($Ext, true);
        } else {
            // The extension is forced
            $Frm = $this->Ext_GetFormat($Ext, false);
        }

        $i = false;
        if (isset($GLOBAL['_OPENTBS_AutoExt'][$Ext])) {
            $i = $GLOBAL['_OPENTBS_AutoExt'][$Ext];
        } elseif ($Frm==='odf') {
            // OpenOffice & LibreOffice documents
            $i = array('main' => 'content.xml', 'br' => '<text:line-break/>', 'frm' => 'odf', 'ctype' => 'application/vnd.oasis.opendocument.', 'pic_path' => 'Pictures/', 'rpl_what' => '&apos;', 'rpl_with' => '\'');
            if ($this->FileExists('styles.xml')) $i['load'] = array('styles.xml'); // styles.xml may contain header/footer contents
            if ($Ext==='odf') $i['br'] = false;
            $ctype = array('t' => 'text', 's' => 'spreadsheet', 'g' => 'graphics', 'f' => 'formula', 'p' => 'presentation', 'm' => 'text-master');
            $i['ctype'] .= $ctype[($Ext[2])];
            $i['pic_ext'] = array('png' => 'png', 'bmp' => 'bmp', 'gif' => 'gif', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'jpe' => 'jpeg', 'jfif' => 'jpeg', 'tif' => 'tiff', 'tiff' => 'tiff');
        } elseif ($Frm==='openxml') {
            // Microsoft Office documents
            if (!isset($this->TBS->OtbsClearMsWord)) $this->TBS->OtbsClearMsWord = true;
            if (!isset($this->TBS->OtbsMsExcelConsistent)) $this->TBS->OtbsMsExcelConsistent = true;
            $this->OpenXML_MapInit();
            if ($this->TBS->OtbsConvertApostrophes) {
                $x = array(chr(226) . chr(128) . chr(152), chr(226) . chr(128) . chr(153));
            } else {
                $x = null;
            }
            $ctype = 'application/vnd.openxmlformats-officedocument.';
            if ($Ext==='docx') {
                $i = array('br' => '<w:br/>', 'frm' => 'openxml', 'ctype' => $ctype . 'wordprocessingml.document', 'pic_path' => 'word/media/', 'rpl_what' => $x, 'rpl_with' => '\'');
                $i['main'] = $this->OpenXML_MapGetMain('wordprocessingml.document.main+xml', 'word/document.xml');
                $i['load'] = $this->OpenXML_MapGetFiles(array('wordprocessingml.header+xml', 'wordprocessingml.footer+xml'));
            } elseif ($Ext==='xlsx') {
                $i = array('br' => false, 'frm' => 'openxml', 'ctype' => $ctype . 'spreadsheetml.sheet', 'pic_path' => 'xl/media/');
                $i['main'] = $this->OpenXML_MapGetMain('spreadsheetml.worksheet+xml', 'xl/worksheets/sheet1.xml');
            } elseif ($Ext==='pptx') {
                $i = array('br' => false, 'frm' => 'openxml', 'ctype' => $ctype . 'presentationml.presentation', 'pic_path' => 'ppt/media/', 'rpl_what' => $x, 'rpl_with' => '\'');
                $i['main'] = $this->OpenXML_MapGetMain('presentationml.slide+xml', 'ppt/slides/slide1.xml');
                $i['load'] = $this->OpenXML_MapGetFiles(array('presentationml.notesSlide+xml'));
            }
            $i['pic_ext'] = array('png' => 'png', 'bmp' => 'bmp', 'gif' => 'gif', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'jpe' => 'jpeg', 'tif' => 'tiff', 'tiff' => 'tiff', 'ico' => 'x-icon', 'svg' => 'svg+xml');
        }

        if ($i!==false) {
            $i['ext'] = $Ext;
            if (!isset($i['load'])) $i['load'] = array();
            $i['load'][] = $i['main']; // add to main file at the end of the files to load
        }
        $this->ExtInfo = $i;
        return (is_array($i)); // return true if the extension is suported
    }

    function Ext_GetFormat(&$Ext, $Search) {
        if (strpos(',odt,ods,odg,odf,odp,odm,ott,ots,otg,otp,', ',' . $Ext . ',') !== false) return 'odf';
        if (strpos(',docx,xlsx,pptx,', ',' . $Ext . ',') !== false) return 'openxml';
        if (!$Search) return false;
        if ($this->FileExists('content.xml')) {
            // OpenOffice documents
            if ($this->FileExists('META-INF/manifest.xml')) {
                $Ext = '?'; // not needed for processing OpenOffice documents
                return 'odf';
            }
        } elseif ($this->FileExists('[Content_Types].xml')) {
            // Ms Office documents
            if ($this->FileExists('word/document.xml')) {
                $Ext = 'docx';
                return 'openxml';
            } elseif ($this->FileExists('xl/workbook.xml')) {
                $Ext = 'xlsx';
                return 'openxml';
            } elseif ($this->FileExists('ppt/presentation.xml')) {
                $Ext = 'pptx';
                return 'openxml';
            }
        }
        return false;
    }

    function Ext_Get() {
        if ( ($this->ExtInfo!==false) && isset($this->ExtInfo['ext']) ) {
            return $this->ExtInfo['ext'];
        } else {
            return false;
        }
    }

    function Ext_GetFrm() {
        if ( ($this->ExtInfo!==false) && isset($this->ExtInfo['frm']) ) {
            return $this->ExtInfo['frm'];
        } else {
            return false;
        }
    }

    function XML_GetInnerVal($Txt, $Tag, $Concat=false) {
        $res = '';
        $p3 = 0;
        $close = '</'.$Tag.'>';
        $close_len = strlen($close);
        $nbr = 0;
        while ( ($p = $this->XML_FoundTagStart($Txt, $Tag, $p3))!==false ) {
            $nbr++;
            $p2 = strpos($Txt, '>', $p);
            if ($p2==false) return $this->RaiseError('(XML) the end of tag '.$Tag.' is not found.');
            $p2++;
            $p3 = strpos($Txt, $close, $p2);
            if ($p3==false) exit("strpos($Txt, $close, $p2) p=$p ; nbr=$nbr");
            if ($p3==false) return $this->RaiseError('(XML) the closing tag '.$Tag.' is not found.');
            $x = substr($Txt, $p2, $p3-$p2);
            if ($Concat===false) {
                return $x;
            } elseif ($res!=='') {
                $res .= $Concat;
            }
            $res .= $x;
            $p3 = $p3 + $close_len;
        }
        return $res;
    }

    function XML_FoundTagStart($Txt, $Tag, $PosBeg) {
        // Found the next tag of the asked type. (Not specific to MsWord, works for any XML)
        // Tag must be prefixed with '<' or '</'.
        $len = strlen($Tag);
        $p = $PosBeg;
        while ($p!==false) {
            $p = strpos($Txt, $Tag, $p);
            if ($p===false) return false;
            $x = substr($Txt, $p+$len, 1);
            if (($x===' ') || ($x==='/') || ($x==='>') ) {
                return $p;
            } else {
                $p = $p+$len;
            }
        }
        return false;
    }

    function XML_DeleteElements(&$Txt, $TagLst, $OnlyInner=false) {
        // Delete all tags of the types given in the list. In fact the entire element is deleted if it's an opening+closing tag.
        $nbr_del = 0;
        foreach ($TagLst as $tag) {
            $t_open = '<'.$tag;
            $t_close = '</'.$tag;
            $p1 = 0;
            while (($p1=$this->XML_FoundTagStart($Txt, $t_open, $p1))!==false) {
                // get the end of the tag
                $pe1 = strpos($Txt, '>', $p1);
                if ($pe1===false) return false; // error in the XML formating
                $p2 = false;
                if (substr($Txt, $pe1-1, 1)=='/') {
                    $pe2 = $pe1;
                } else {
                    // it's an opening+closing
                    $p2 = $this->XML_FoundTagStart($Txt, $t_close, $pe1);
                    if ($p2===false) return false; // error in the XML formating
                    $pe2 = strpos($Txt, '>', $p2);
                }
                if ($pe2===false) return false; // error in the XML formating
                // delete the tag
                if ($OnlyInner) {
                    if ($p2!==false) $Txt = substr_replace($Txt, '', $pe1+1, $p2-$pe1-1);
                    $p1 = $pe1; // for next search
                } else {
                    $Txt = substr_replace($Txt, '', $p1, $pe2-$p1+1);
                }
            }
        }
        return $nbr_del;
    }

    function OpenXML_RidPrepare($DocPath, $ImageName) {
        /* Return the RelationId if the image if it's already referenced in the Relation file in the archive.
        Otherwise, OpenTBS prepares info to add this information at the end of the merging.
        $ImageName must be the name of the image, without path. This is because OpenXML needs links to be relative to the active document. In our case, image files are always stored into subfolder 'media'.
        */

        if (!isset($this->OpenXmlRid[$DocPath])) {
            $o = (object) null;
            $o->RidLst = array();
            $o->RidNew = array();
            $DocName = basename($DocPath);
            $o->FicPath = str_replace($DocName,'_rels/'.$DocName.'.rels',$DocPath);
            $o->FicType = false; // false = to check, 0 = exist in the archive, 1 = to add in the archive
            $o->FicIdx = false; // in case of FicType=0
			$this->OpenXmlRid[$DocPath] = &$o;
		} else {
            $o = &$this->OpenXmlRid[$DocPath];
        }

        if ($o->FicType===false) {
            $FicIdx = $this->FileGetIdx($o->FicPath);
            if ($FicIdx===false) {
                $o->FicType = 1;
                $o->FicTxt = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
            } else {
                $o->FicIdx = $FicIdx;
                $o->FicType = 0;
                $Txt = $this->FileRead($FicIdx, true);
                $o->FicTxt = $Txt;
                // read existing Rid in the file
                $zImg = ' Target="media/';
                $zId  = ' Id="';
                $p = -1;
                while (($p = strpos($Txt, $zImg, $p+1))!==false) {
                    // Get the image name
                    $p1 = $p + strlen($zImg);
                    $p2 = strpos($Txt, '"', $p1);
                    if ($p2===false) return $this->RaiseError("(OpenXML) end of attribute Target not found in position ".$p1." of subfile ".$o->FicPath);
                    $Img = substr($Txt, $p1, $p2 -$p1 -1);
                    // Get the Id
                    $p1 = strrpos(substr($Txt,0,$p), '<');
                    if ($p1===false) return $this->RaiseError("(OpenXML) begining of tag not found in position ".$p." of subfile ".$o->FicPath);
                    $p1 = strpos($Txt, $zId, $p1);
                    if ($p1!==false) {
                        $p1 = $p1 + strlen($zId);
                        $p2 = strpos($Txt, '"', $p1);
                        if ($p2===false) return $this->RaiseError("(OpenXML) end of attribute Id not found in position ".$p1." of subfile ".$o->FicPath);
                        $Rid = substr($Txt, $p1, $p2 -$p1 -1);
                        $o->RidLst[$Img] = $Rid;
                    }
                }
            }
        }

        if (isset($o->RidLst[$ImageName])) return $o->RidLst[$ImageName];

        // Add the Rid in the information
        $NewRid = 'opentbs'.(1+count($o->RidNew));
        $o->RidLst[$ImageName] = $NewRid;
        $o->RidNew[$ImageName] = $NewRid;

        return $NewRid;

    }

    function OpenXML_RidCommit ($Debug) {

        foreach ($this->OpenXmlRid as $o) {
            // search position for insertion
            $p = strpos($o->FicTxt, '</Relationships>');
            if ($p===false) return $this->RaiseError("(OpenXML) closing tag </Relationships> not found in subfile ".$o->FicPath);
            // build the string to instert
            $x = '';
            foreach ($o->RidNew as $img=>$rid) {
                $x .= '<Relationship Id="'.$rid.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$img.'"/>';
            }
            // insert
            $o->FicTxt = substr_replace($o->FicTxt, $x, $p, 0);
            if ($o->FicType==1) {
                $this->FileAdd($o->FicPath, $o->FicTxt);
            } else {
                $this->FileReplace($o->FicIdx, $o->FicTxt);
            }
            // debug mode
            if ($Debug) $this->DebugLst[$o->FicPath] = $o->FicTxt;
        }

    }

    function OpenXML_CTypesPrepare($FileOrExt, $ct='') {
        /* this function prepare information for editing the '[Content_Types].xml' file.
        It needs to be completed when a new picture file extension is added in the document.
        */

        $p = strrpos($FileOrExt, '.');
        $ext = ($p===false) ? $FileOrExt : substr($FileOrExt, $p+1);
        $ext = strtolower($ext);

        if (isset($this->OpenXmlCTypes[$ext]) && ($this->OpenXmlCTypes[$ext]!=='') ) return;

        if (($ct==='') && isset($this->ExtInfo['pic_ext'][$ext])) $ct = 'image/'.$this->ExtInfo['pic_ext'][$ext];

        $this->OpenXmlCTypes[$ext] = $ct;

    }

    function OpenXML_CTypesCommit($Debug) {

        $file = '[Content_Types].xml';
        $idx = $this->FileGetIdx($file);
        if ($idx===false) {
            $Txt = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>';
        } else {
            $Txt = $this->FileRead($idx, true);
        }

        $x = '';
        foreach ($this->OpenXmlCTypes as $ext=>$ct) {
            $p = strpos($Txt, ' Extension="'.$ext.'"');
            if ($p===false) {
                if ($ct==='') {
                    $this->RaiseError("(OpenXML) '"+$ext+"' is not an picture's extension recognize by OpenTBS.");
                } else {
                    $x .= '<Default Extension="'.$ext.'" ContentType="'.$ct.'"/>';
                }
            }
        }


        if ($x!=='') {

            $p = strpos($Txt, '</Types>'); // search position for insertion
            if ($p===false) return $this->RaiseError("(OpenXML) closing tag </Types> not found in subfile ".$file);
            $Txt = substr_replace($Txt, $x, $p ,0);

            // debug mode
            if ($Debug) $this->DebugLst[$file] = $Txt;

            if ($idx===false) {
                $this->FileAdd($file, $Txt);
            } else {
                $this->FileReplace($idx, $Txt);
            }

        }

    }

    function OpenXML_FirstPicAtt($Txt, $Pos, $Backward) {
        // search the first image element in the given direction. Two types of image can be found. Return the value required for "att" parameter.
        $TypeVml = '<v:imagedata ';
        $TypeDml = '<a:blip ';

        if ($Backward) {
            // search the last image position this code is compatible with PHP 4
            $p = -1;
            $pMax = -1;
            $t_curr = $TypeVml;
            $t = '';
            do {
                $p = strpos($Txt, $t_curr, $p+1);
                if ( ($p===false) || ($p>=$Pos) ) {
                    if ($t_curr===$TypeVml) {
                        // we take a new search for the next type of image
                        $t_curr = $TypeDml;
                        $p = -1;
                    } else {
                        $p = false;
                    }
                } elseif ($p>$pMax) {
                    $pMax = $p;
                    $t = $t_curr;
                }
            } while ($p!==false);
        } else {
            $p1 = strpos($Txt, $TypeVml, $Pos);
            $p2 = strpos($Txt, $TypeDml, $Pos);
            if (($p1===false) && ($p2===false)) {
                $t = '';
            } elseif ($p1===false) {
                $t = $TypeDml;
            } elseif ($p2===false) {
                $t = $TypeVml;
            } else {
                $t = ($p1<$p2) ? $TypeVml : $TypeDml;
            }
        }

        if ($t===$TypeVml) {
            return 'v:imagedata#r:id';
        } elseif ($t===$TypeDml) {
            return 'a:blip#r:embed';
        } else {
            return false;
        }

    }

    function OpenXML_MapInit() {
        // read the Content_Type XML file and save a sumup in the OpenXmlMap property.

        $this->OpenXmlMap = array();
        $Map =& $this->OpenXmlMap;

        $file = '[Content_Types].xml';
        $idx = $this->FileGetIdx($file);
        if ($idx===false) return;

        $Txt = $this->FileRead($idx, true);

        $type = ' ContentType="application/vnd.openxmlformats-officedocument.';
        $type_l = strlen($type);
        $name = ' PartName="';
        $name_l = strlen($name);

        $p = -1;
        while ( ($p=strpos($Txt, '<', $p+1))!==false) {
            $pe = strpos($Txt, '>', $p);
            if ($pe===false) return; // syntax error in the XML
            $x = substr($Txt, $p+1, $pe-$p-1);
            $pi = strpos($x, $type);
            if ($pi!==false) {
                $pi = $pi + $type_l;
                $pc = strpos($x, '"', $pi);
                if ($pc===false) return; // syntax error in the XML
                $ShortType = substr($x, $pi, $pc-$pi); // content type's short value
                $pi = strpos($x, $name);
                if ($pi!==false) {
                    $pi = $pi + $name_l;
                    $pc = strpos($x, '"', $pi);
                    if ($pc===false) return; // syntax error in the XML
                    $Name = substr($x, $pi, $pc-$pi); // name
                    if ($Name[0]=='/') $Name = substr($Name,1); // fix the file path
                    if (!isset($Map[$ShortType])) $Map[$ShortType] = array();
                    $Map[$ShortType][] = $Name;
                }
            }
            $p = $pe;
        }

    }

    function OpenXML_MapGetFiles($ShortTypes) {
        // Return all values for a given type (or array of types) in the map.
        if (is_string($ShortTypes)) $ShortTypes = array($ShortTypes);
        $res = array();
        foreach ($ShortTypes as $type) {
            if (isset($this->OpenXmlMap[$type])) {
                $val = $this->OpenXmlMap[$type];
                foreach ($val as $file) $res[] = $file;
            }
        }
        return $res;
    }

    function OpenXML_MapGetMain($ShortType, $Default) {
        // Return all values for a given type (or array of types) in the map.
        if (isset($this->OpenXmlMap[$ShortType])) {
            return $this->OpenXmlMap[$ShortType][0];
        } else {
            return $Default;
        }
    }

    function OpenXML_ChartInit() {

        $this->OpenXmlCharts = array();

        foreach ($this->CdFileByName as $f => $i) {
            if (strpos($f, '/charts/')!==false) {
                $f = explode('/',$f);
                $n = count($f) -1;
                if ( ($n>=2) && ($f[$n-1]==='charts') ) {
                    $f = $f[$n]; // name of the xml file
                    if (substr($f,-4)==='.xml') {
                        $f = substr($f,0,strlen($f)-4);
                        $this->OpenXmlCharts[$f] = array('idx'=>$i, 'clean'=>false, 'series'=>false);
                    }
                }
            }
        }

    }

    function OpenXML_ChartDebug($nl, $sep, $bull) {

        if (!isset($this->OpenXmlCharts)) $this->OpenXML_ChartInit();

        echo $nl;
        echo $nl."Charts inside the document:";
        echo $nl."---------------------------";

        // list of supported charts
        $nbr = 0;
        foreach ($this->OpenXmlCharts as $key => $info) {
            $nbr++;
            if (!isset($info['series_nbr'])) {
                $txt = $this->FileRead($info['idx'], true);
                $info['series_nbr'] = substr_count($txt, '<c:ser>');
            }
            echo $bull."id: '".$key."' , number of series: ".$info['series_nbr'];
        }

        if ($this->TbsCurrIdx===false) {
            echo $bull."(unable to scann more because no subfile is loaded)";
        } else {
            $x = ' ProgID="MSGraph.Chart.';
            $x_len = strlen($x);
            $p = 0;
            $txt = $this->TBS->Source;
            while (($p=strpos($txt, $x, $p))!==false) {
                // check that the text is inside an xml tag
                $p = $p + $x_len;
                $p1 = strpos($txt, '>', $p);
                $p2 = strpos($txt, '<', $p);
                if ( ($p1!==false) && ($p2!==false) && ($p1<$p2) ) {
                    $nbr++;
                    $p1 = strpos($txt, '"', $p);
                    $z = substr($txt, $p, $p1-$p);
                    echo $bull."1 chart created using MsChart version ".$z." (series can't be merged with OpenTBS)";
                }
            }
        }

        if ($nbr==0) echo $bull."(none)";

    }

    function OpenXML_ChartSeriesFound(&$Txt, $SeriesNameOrNum, $OnlyBounds=false) {

        $IsNum = is_numeric($SeriesNameOrNum);
        if ($IsNum) {
            $p = strpos($Txt, '<c:order val="'.($SeriesNameOrNum-1).'"/>');
        } else {
            $SeriesNameOrNum = htmlentities($SeriesNameOrNum);
            $p = strpos($Txt, '>'.$SeriesNameOrNum.'<');
        }
        if ($p===false) return false;

        if (!$IsNum) $p++;
        $res = array('p'=>$p);

        if ($OnlyBounds) {
            $p1 = clsTinyButStrong::f_Xml_FindTagStart($Txt, 'c:ser', true, $p, false, true);
            $x = '</c:ser>';
            $p2 = strpos($Txt, '</c:ser>', $p1);
            if ($p2===false) return false;
            $res['l'] = $p2 + strlen($x) - $p1;
            return $res;
        }

        $end_tag = '</c:ser>';
        $end = strpos($Txt, '</c:ser>', $p);
        $len = $end + strlen($end_tag) - $p;
        $res['l'] = $len;

        $x = substr($Txt, $p, $len);

        // Legend, may be abensent
        $p = 0;
        if ($IsNum) {
            $p1 = strpos($x, '<c:tx>');
            if ($p1>0) {
                $p2 = strpos($x, '</c:tx>', $p1);
                $tag = '<c:v>';
                $p1 = strpos($x, $tag, $p1);
                if ( ($p1!==false) && ($p1<$p2) ) {
                    $p1 = $p1 + strlen($tag);
                    $p2 = strpos($x, '<', $p1);
                    $res['leg_p'] = $p1;
                    $res['leg_l'] = $p2 - $p1;
                    $p = $p2;
                }
            }
        } else {
            $res['leg_p'] = 0;
            $res['leg_l'] = strlen($SeriesNameOrNum);
        }

        // Data X & Y, we assume that (X or Category) are always first and (Y or Value) are always second
        for ($i=1; $i<=2; $i++) {
            $p1 = strpos($x, '<c:ptCount ', $p);
            if ($p1===false) return false;
            $p2 = strpos($x, 'Cache>', $p1); // the closing tag can be </c:numCache> or </c:strCache>
            if ($p2===false) return false;
            $p2 = $p2 - 7;
            $res['point'.$i.'_p'] = $p1;
            $res['point'.$i.'_l'] = $p2 - $p1;
            $p = $p2;
        }

        return $res;

    }

    function OpenXML_ChartChangeSeries($ChartNameOrNum, $SeriesNameOrNum, $NewValues, $NewLegend=false, $CopyFromSeries=false) {

        if (!isset($this->OpenXmlCharts)) $this->OpenXML_ChartInit();

        // search the chart
        $ref = ''.$ChartNameOrNum;
        if (!isset($this->OpenXmlCharts[$ref])) $ref = 'chart'.$ref;
        if (!isset($this->OpenXmlCharts[$ref])) return $this->RaiseError("(ChartChangeSeries) unable to found the chart corresponding to '".$ChartNameOrNum."'.");

        $chart =& $this->OpenXmlCharts[$ref];
        $Txt = $this->TbsStoreGet($chart['idx'], 'ChartChangeSeries');
        if ($Txt===false) return false;

        if (!$chart['clean']) {
            // delete tags that refere to the XLSX file containing original data
            //$this->XML_DeleteElements($Txt, array('c:externalData', 'c:f'));
            $chart['nbr'] = substr_count($Txt, '<c:ser>');
            $chart['clean'] = true;
        }

        $Delete = ($NewValues===false);
        $ser = $this->OpenXML_ChartSeriesFound($Txt, $SeriesNameOrNum, $Delete);
        if ($ser===false) return $this->RaiseError("(ChartChangeSeries) unable to found series '".$SeriesNameOrNum."' in the chart '".$ref."'.");

        if ($Delete) {

            $Txt = substr_replace($Txt, '', $ser['p'], $ser['l']);

        } else {


            $point1 = '';
            $point2 = '';
            $i = 0;
            $v = reset($NewValues);
            if (is_array($v)) {
                // syntax 2: $NewValues = array( array('cat1','cat2',...), array(val1,val2,...) );
                $k = key($NewValues);
                $key_lst = &$NewValues[$k];
                $val_lst = &$NewValues[1];
                $simple = false;
            } else {
                // syntax 1: $NewValues = array('cat1'=>val1, 'cat2'=>val2, ...);
                $key_lst = &$NewValues;
                $val_lst = &$NewValues;
                $simple = true;
            }
            foreach ($key_lst as $k=>$v) {
                if ($simple) {
                    $x = $k;
                    $y = $v;
                } else {
                    $x = $v;
                    $y = isset($val_lst[$k]) ? $val_lst[$k] : null;
                }
                if ( (!is_null($y)) && ($y!==false) && ($y!=='') && ($y!=='NULL') ) {
                    $point1 .= '<c:pt idx="'.$i.'"><c:v>'.$x.'</c:v></c:pt>';
                    $point2 .= '<c:pt idx="'.$i.'"><c:v>'.$y.'</c:v></c:pt>';
                    $i++;
                }
            }
            $point1 = '<c:ptCount val="'.$i.'"/>'.$point1;
            $point2 = '<c:ptCount val="'.$i.'"/>'.$point2;

            // change info in reverse order of placement in order to avoid exention problems
            $p = $ser['p'];
            $Txt = substr_replace($Txt, $point2, $p+$ser['point2_p'], $ser['point2_l']);
            $Txt = substr_replace($Txt, $point1, $p+$ser['point1_p'], $ser['point1_l']);
            if ( (is_string($NewLegend)) && isset($ser['leg_p']) && ($ser['leg_p']<$ser['point1_p']) ) {
                $NewLegend = htmlspecialchars($NewLegend);
                $Txt = substr_replace($Txt, $NewLegend, $p+$ser['leg_p'], $ser['leg_l']);
            }

        }

        $this->TbsStorePut($chart['idx'], $Txt, true);

        return true;

    }

    function OpenXML_SharedStrings_Prepare() {

        $file = 'xl/sharedStrings.xml';
        $idx = $this->FileGetIdx($file);
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'Excel SharedStrings');
        if ($Txt===false) return false;
        $this->TbsStorePut($idx, $Txt); // save for any further usage

        $this->OpenXmlSharedStr = array();
        $this->OpenXmlSharedSrc =& $this->TbsStoreLst[$idx]['src'];

    }

    function OpenXML_SharedStrings_GetVal($id) {
        // this function return the XML content of the string and put previous met values in cache
        if (!isset($this->OpenXmlSharedStr)) $this->OpenXML_SharedStrings_Prepare();

        $Txt =& $this->OpenXmlSharedSrc;

        if (!isset($this->OpenXmlSharedStr[$id])) {
            $last_id = count($this->OpenXmlSharedStr) - 1; // last id in the cache
            if ($last_id<0) {
                $p2 = 0; // no items found yet
            } else {
                $p2 = $this->OpenXmlSharedStr[$last_id]['end'];
            }
            $x1 = '<si'; // SharedString Item
            $x1_len = strlen($x1);
            $x2 = '</si>';
            while ($last_id<$id) {
                $last_id++;
                $p1 = strpos($Txt, $x1, $p2+1);
                if ($p1===false) return $this->RaiseError("(Excel SharedStrings) id $id is searched but id $last_id is not found.");
                $p1 = strpos($Txt, '>', $p1+$x1_len)+1;
                $p2 = strpos($Txt, $x2, $p1);
                if ($p2===false) return $this->RaiseError("(Excel SharedStrings) id $id is searched but no closing tag found for id $last_id.");
                $this->OpenXmlSharedStr[$last_id] = array('beg'=>$p1, 'end'=>$p2, 'len'=>($p2-$p1));
            }
        }

        $str =& $this->OpenXmlSharedStr[$id];

        return substr($Txt, $str['beg'], $str['len']);

    }

    function MsExcel_ConvertToRelative(&$Txt) {
        // <row r="10" ...> attribute "r" is optional since missing row are added using <row />
        // <c r="D10" ...> attribute "r" is optional since missing cells are added using <c />
        $Loc = new clsTbsLocator;
        $this->MsExcel_ConvertToRelative_Item($Txt, $Loc, 'row', 'r', true);
    }

    function MsExcel_ConvertToRelative_Item(&$Txt, &$Loc, $Tag, $Att, $IsRow) {
        // convert tags $Tag which have a position (defined with attribute $Att) into relatives tags without attribute $Att. Missing tags are added as empty tags.
        $item_num = 0;
        $tag_len = strlen($Tag);
        $missing = '<'.$Tag.' />';
        $closing = '</'.$Tag.'>';
        $p = 0;
        while (($p=clsTinyButStrong::f_Xml_FindTagStart($Txt, $Tag, true, $p, true, true))!==false) {

            $Loc->PrmPos = array();
            $Loc->PrmLst = array();
            $p2 = $p + $tag_len + 2; // count the char '<' before and the char ' ' after
            $PosEnd = strpos($Txt, '>', $p2);
            clsTinyButStrong::f_Loc_PrmRead($Txt,$p2,true,'\'"','<','>',$Loc, $PosEnd, true); // read parameters
            if (isset($Loc->PrmPos[$Att])) {
                // attribute found
                $r = $Loc->PrmLst[$Att];
                if ($IsRow) {
                    $r = intval($r);
                } else {
                    $r = $this->MsExcel_ColNum($r);
                }
                $missing_nbr = $r - $item_num -1;
                if ($missing_nbr<0) {
                    return $this->RaiseError('(Excel Consistency) error in counting items <'.$Tag.'>, found number '.$r.', previous was '.$item_num);
                } else {
                    // delete the $Att attribute
                    $pp = $Loc->PrmPos[$Att];
                    $pp[3]--; //while ($Txt[$pp[3]]===' ') $pp[3]--; // external end of the attribute, may has an extra spaces
                    $x_p = $pp[0];
                    $x_len = $pp[3] - $x_p +1;
                    $Txt = substr_replace($Txt, '', $x_p, $x_len);
                    $PosEnd = $PosEnd - $x_len;
                    // If it's a cell, we look if it's a good idea to replace the shared string
                    if ( (!$IsRow) && isset($Loc->PrmPos['t']) && ($Loc->PrmLst['t']==='s') ) $this->MsExcel_ReplaceString($Txt, $p, $PosEnd);
                    // add missing items before the current item
                    if ($missing_nbr>0) {
                        $x = str_repeat($missing, $missing_nbr);
                        $x_len = strlen($x);
                        $Txt = substr_replace($Txt, $x, $p, 0);
                        $PosEnd = $PosEnd + $x_len;
                        $x = ''; // empty the memory
                    }
                }
                $item_num = $r;
            } else {
                // nothing to change the item is already relative
                $item_num++;
            }

            if ($IsRow && ($Txt[$PosEnd-1]!=='/')) {
                // It's a row item that may contain columns
                $x_p = strpos($Txt, $closing, $PosEnd);
                if ($x_p===false) return $this->RaiseError('(Excel Consistency) closing row tag is not found.');
                $x_len0 = $x_p - $PosEnd -1;
                $x = substr($Txt, $PosEnd+1, $x_len0);
                $this->MsExcel_ConvertToRelative_Item($x, $Loc, 'c', 'r', false);
                $Txt = substr_replace($Txt, $x, $PosEnd+1, $x_len0);
                $x_len = strlen($x);
                $p = $x_p + $x_len - $x_len0;
            } else {
                $p = $PosEnd;
            }

        }

    }

    function MsExcel_ColNum($ColRef) {
        // return the column number from a reference like "B3"
        $num = 0;
        $rank = 0;
        for ($i=strlen($ColRef)-1;$i>=0;$i--) {
            $l = $ColRef[$i];
            if (!is_numeric($l)) {
                $l = ord(strtoupper($l)) -64;
                if ($l>0 && $l<27) {
                    $num = $num + $l*pow(26,$rank);
                } else {
                    return $this->RaiseError('(Excel Consistency) Reference of cell \''.$ColRef.'\' cannot be recognized.');
                }
                $rank++;
            }
        }
        return $num;
    }

    function MsExcel_DeleteFormulaResults(&$Txt) {
        // In order to refresh the formula results when the merged XLSX is opened, then we delete all <v> elements having a formula.
        $c_close = '</c>';
        $p = 0;
        while (($p=clsTinyButStrong::f_Xml_FindTagStart($Txt, 'f', true, $p, true, true))!==false) {
            $c_p = strpos($Txt, $c_close, $p);
            if ($c_p===false) return false; // error in the XML
            $x_len0 = $c_p - $p;
            $x = substr($Txt, $p, $x_len0);
            $this->XML_DeleteElements($x, array('v'));
            $Txt = substr_replace($Txt, $x, $p, $x_len0);
            $p = $p + strlen($x);
        }
    }

    function MsExcel_ReplaceString(&$Txt, $p, &$PosEnd) {
        // replace a SharedString into an InlineStr only if the string contains a TBS field
        static $c = '</c>';
        static $v1 = '<v>';
        static $v1_len = 3;
        static $v2 = '</v>';
        static $v2_len = 4;
        static $notbs = array();

        // found position of the <c> element, and extract its contents
        $p_close = strpos($Txt, $c, $PosEnd);
        if ($p_close===false) return;
        $x_len = $p_close - $p;
        $x = substr($Txt, $p, $x_len); // [<c ...> ... ]</c>

        // found position of the <v> element, and extract its contents
        $v1_p = strpos($x, $v1);
        if ($v1_p==false) return false;
        $v2_p = strpos($x, $v2, $v1_p);
        if ($v2_p==false) return false;
        $vt = substr($x, $v1_p+$v1_len, $v2_p - $v1_p - $v1_len);

        // extract the SharedString id, and retrieve the corresponding text
        $v = intval($vt);
        if (($v==0) && ($vt!='0')) return false;
        if (isset($notbs[$v])) return true;
        $s = $this->OpenXML_SharedStrings_GetVal($v);

        // if the SharedSring has no TBS field, then we save the id in a list of known id, and we leave the function
        if (strpos($s, $this->TBS->_ChrOpen)===false) {
            $notbs[$v] = true;
            return true;
        }

        // prepare the replace
        $x1 = substr($x, 0, $v1_p);
        $x3 = substr($x, $v2_p + $v2_len);
        $x2 = '<is>'.$s.'</is>';
        $x = str_replace(' t="s"', ' t="inlineStr"', $x1).$x2.$x3;

        $Txt = substr_replace($Txt, $x, $p, $x_len);

        $PosEnd = $p + strlen($x); // $PosEnd is used to search the next item, so we update it

    }

    function MsExcel_ChangeCellType(&$Txt, &$Loc, $Ope) {
        // change the type of a cell in an XLSX file

        $Loc->PrmLst['xlsxok'] = $Ope; // avoid the field to be processed twice

        if ($Ope==='xlsxString') return true;

        static $OpeLst = array('xlsxBool'=>' t="b"', 'xlsxDate'=>'', 'xlsxNum'=>'');

        if (!isset($OpeLst[$Ope])) return false;

        $t0 = clsTinyButStrong::f_Xml_FindTagStart($Txt, 'c', true, $Loc->PosBeg, false, true);
        if ($t0===false) return false; // error in the XML structure

        $te = strpos($Txt, '>', $t0);
        if ( ($te===false) || ($te>$Loc->PosBeg) ) return false; // error in the XML structure

        $len = $te - $t0 + 1;
        $c_open = substr($Txt, $t0, $len); // '<c ...>'
        $c_open = str_replace(' t="inlineStr"', $OpeLst[$Ope], $c_open);

        $t1 = strpos($Txt, '</c>', $te);
        if ($t1===false) return false; // error in the XML structure

        $p_is1 = strpos($Txt, '<is>', $te);
        if (($p_is1===false) || ($p_is1>$t1) ) return false; // error in the XML structure

        $is2 = '</is>';
        $p_is2 = strpos($Txt, $is2, $p_is1);
        if (($p_is2===false) || ($p_is2>$t1) ) return false; // error in the XML structure
        $p_is2 = $p_is2 + strlen($is2); // move to end the of the tag

        $middle_len = $p_is1 - $te - 1;
        $middle = substr($Txt, $te + 1, $middle_len); // text bewteen <c...> and <is>

        // new tag to replace <is>...</is>
        static $v = '<v>[]</v>';
        $v_len = strlen($v);
        $v_pos = strpos($v, '[]');

        $x = $c_open.$middle.$v;

        $Txt = substr_replace($Txt, $x, $t0, $p_is2 - $t0);

        // move the TBS field
        $p_fld = $t0 + strlen($c_open) + $middle_len + $v_pos;
        $Loc->PosBeg = $p_fld;
        $Loc->PosEnd = $p_fld +1;

    }

    function MsExcel_SheetInit() {

        if (isset($this->MsExcel_Sheets)) return;

        $this->MsExcel_Sheets = array();   // sheet info sorted by location
        $this->MsExcel_SheetsById = array(); // shorcut for ids
        $this->MsExcel_SheetsByName = array(); // shorcut for names

        $name = 'xl/workbook.xml';
        $idx = $this->FileGetIdx($name);
        $this->MsExcel_Sheets_FileId = $idx;
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'SheetInfo'); // use the store, so the file will be available for editing if needed
        if ($Txt===false) return false;
        $this->TbsStorePut($idx, $Txt);

        // scann sheet list
        $p = 0;
        $idx = 0;
        $rels = array();
        while ($loc=clsTinyButStrong::f_Xml_FindTag($Txt, 'sheet', true, $p, true, false, true, true) ) {
            if (isset($loc->PrmLst['sheetid'])) {
                $id = $loc->PrmLst['sheetid']; // actual parameter is 'sheetId'
                $this->MsExcel_Sheets[$idx] = $loc;
                if (isset($loc->PrmLst['r:id'])) $rels[$loc->PrmLst['r:id']] = $idx;
                $this->MsExcel_SheetsById[$id] =& $this->MsExcel_Sheets[$idx];
                if (isset($loc->PrmLst['name'])) $this->MsExcel_SheetsByName[$loc->PrmLst['name']] =& $this->MsExcel_Sheets[$idx];
                $idx++;
            }
            $p = $loc->PosEnd;
        }

        // retrieve sheet files
        $Txt = $this->FileRead('xl/_rels/workbook.xml.rels');
        if ($Txt===false) return false;

        $p = 0;
        while ($loc=clsTinyButStrong::f_Xml_FindTag($Txt, 'Relationship', true, $p, true, false, true, false) ) {
            if (isset($loc->PrmLst['id']) && isset($loc->PrmLst['target']) ) {
                $rid = $loc->PrmLst['id'];
                if (isset($rels[$rid])) $this->MsExcel_Sheets[$rels[$rid]]->xlsxTarget = 'xl/'.$loc->PrmLst['target'];
            }
            $p = $loc->PosEnd;
        }

    }

    function MsExcel_SheetGet($IdOrName, $Caller, $CheckTarget=false) {
        $this->MsExcel_SheetInit();
        if (isset($this->MsExcel_SheetsByName[$IdOrName])) {
            $loc = $this->MsExcel_SheetsByName[$IdOrName];
        } elseif (isset($this->MsExcel_SheetsById[$IdOrName])) {
            $loc = $this->MsExcel_SheetsById[$IdOrName];
        } else {
            return $this->RaiseError("($Caller) The sheet '$IdOrName' is not found inside the Workbook. Try command OPENTBS_DEBUG_INFO to check all sheets inside the current Workbook.");
        }
        if ($CheckTarget && (!isset($loc->xlsxTarget)) )  return $this->RaiseError("($Caller) Error with sheet '$IdOrName'. The corresponding XML subfile is not referenced.");
        return $loc;
    }

    function MsExcel_SheetDebug($nl, $sep, $bull) {

        $this->MsExcel_SheetInit();

        echo $nl;
        echo $nl."Sheets in the Workbook:";
        echo $nl."-----------------------";
        foreach ($this->MsExcel_Sheets as $loc) {
            $name = str_replace(array('&amp;','&quot;','&lt;','&gt;'), array('&','"','<','>'), $loc->PrmLst['name']);
            echo $bull."id: ".$loc->PrmLst['sheetid'].", name: [".$name."]";
            if (isset($loc->PrmLst['state'])) echo ", state: ".$loc->PrmLst['state'];
        }

    }

    function MsExcel_SheetDeleteAndDisplay() {

        if (!isset($this->OtbsSheetOk)) return;
        if ( (count($this->OtbsSheetDelete)==0) && (count($this->OtbsSheetVisible)==0) ) return;

        $this->MsExcel_SheetInit();
        $Txt = $this->TbsStoreGet($this->MsExcel_Sheets_FileId, 'Sheet Delete and Display');

        $close = '</table:table>';
        $close_len = strlen($close);

        $styles_to_edit = array();
        $change = false;
        $deleted = array();

        // process sheet in reverse order of their positions
        for ($idx = count($this->MsExcel_Sheets) - 1; $idx>=0; $idx--) {
            $loc = $this->MsExcel_Sheets[$idx];
            $id = 'i:'.$loc->PrmLst['sheetid'];
            $name = 'n:'.$loc->PrmLst['name']; // the value in the name attribute is XML protected
            if ( isset($this->OtbsSheetDelete[$name]) || isset($this->OtbsSheetDelete[$id]) ) {
                // Delete the sheet
                $Txt = substr_replace($Txt, '', $loc->PosBeg, $loc->PosEnd - $loc->PosBeg +1);
                $this->FileReplace($loc->xlsxTarget, false); // mark the target file to be deleted
                $change = true;
                $deleted[$loc->PrmLst['sheetid']] = $loc->PrmLst['name'];
                unset($this->OtbsSheetDelete[$name]);
                unset($this->OtbsSheetDelete[$id]);
                unset($this->OtbsSheetVisible[$name]);
                unset($this->OtbsSheetVisible[$id]);
            } elseif ( isset($this->OtbsSheetVisible[$name]) || isset($this->OtbsSheetVisible[$id]) ) {
                // Hide or display the sheet
                $visible = (isset($this->OtbsSheetVisible[$name])) ? $this->OtbsSheetVisible[$name] : $this->OtbsSheetVisible[$id];
                $state = ($visible) ? 'visible' : 'hidden';
                if (!$visible) $change = true;
                if (isset($loc->PrmLst['state'])) {
                    $pi = $loc->PrmPos['state'];
                    $Txt = substr_replace($Txt, $pi[4].$state.$pi[4], $pi[2], $pi[3]-$pi[2]);
                } elseif(!$visible) {
                    // add the attribute
                    $Txt = substr_replace($Txt, 'state="hidden" ', $loc->PosBeg + strlen('<sheet '), 0);
                }
                unset($this->OtbsSheetVisible[$name]);
                unset($this->OtbsSheetVisible[$id]);
            }
        }

        // if they are deleted or hidden sheet, then it could be the active sheet, so we delete the active tab information
        // note: activeTab attribute seems to not be a sheet id
        if ($change){
            $x = ' activeTab="';
            $p1 = strpos($Txt, $x);
            if ($p1!==false) {
                $p2 = strpos($Txt, '"', $p1 + strlen($x));
                if ($p2!==false) {
                    $Txt = substr_replace($Txt, '', $p1, $p2 - $p1 +1);
                }
            }
        }

        // delete <definedName> elements that refer to a deleted sheet
        foreach ($deleted as $name) {
            $name2 = str_replace(array('&quot;','\''), array('"','\'\''), $name);
            do {
                $p = strpos($Txt, "'".$name2."'");
                if ($p!==false) {
                    $p2 = strpos($Txt, '>', $p);
                    $p1 = strrpos(substr($Txt, 0, $p), '<');
                    if ( ($p1!==false) && ($p2!==false) ) {
                        $Txt = substr_replace($Txt, '', $p1, $p2 - $p1 +1);
                    } else {
                        $p = false;
                    }
                }
            } while ($p!==false);
            //<pivotCaches><pivotCache cacheId="1" r:id="rId5"/></pivotCaches>
        }
        $Txt = str_replace('<pivotCaches></pivotCaches>', '', $Txt); // can make Excel error, no problem with <definedNames>

        // store the result
        $this->TbsStorePut($this->MsExcel_Sheets_FileId, $Txt);

        $this->TbsSheetCheck();

        // see http://ankushbhatia.wordpress.com/2010/02/11/how-to-delete-a-worksheet-from-excel-using-open-xml-sdk-2-0/
        if (count($deleted)>0) {
            // Delete the CalcChain file if any. Ms Excel display an error if this file contains bad referenced cells. But since the cells in this file may not all contain sheet id, it is better to delete the whole file (it is optional).
            $idx = $this->FileGetIdx('xl/calcChain.xml');
            if ($idx!==false) $this->FileReplace($idx, false);
        }

    }

    // Cleaning tags in MsWord

    function MsWord_Clean(&$Txt) {
        $Txt = str_replace('<w:lastRenderedPageBreak/>', '', $Txt); // faster
        $this->XML_DeleteElements($Txt, array('w:proofErr', 'w:noProof', 'w:lang', 'w:lastRenderedPageBreak'));
        $this->MsWord_CleanSystemBookmarks($Txt);
        $this->MsWord_CleanRsID($Txt);
        $this->MsWord_CleanDuplicatedLayout($Txt);
        if ($this->TBS->OtbsSpacePreserve) $this->MsWord_CleanSpacePreserve($Txt);
    }

    function MsWord_CleanSystemBookmarks(&$Txt) {
        // Delete GoBack hidden bookmarks that appear since Office 2010. Example: <w:bookmarkStart w:id="0" w:name="_GoBack"/><w:bookmarkEnd w:id="0"/>

        $x = ' w:name="_GoBack"/><w:bookmarkEnd ';
        $x_len = strlen($x);

        $b = '<w:bookmarkStart ';
        $b_len = strlen($b);

        $nbr_del = 0;

        $p = 0;
        while ( ($p=strpos($Txt, $x, $p))!==false ) {
            $pe = strpos($Txt, '>', $p + $x_len);
            if ($pe===false) return false;
            $pb = strrpos(substr($Txt,0,$p) , '<');
            if ($pb===false) return false;
            if (substr($Txt, $pb, $b_len)===$b) {
                $Txt = substr_replace($Txt, '', $pb, $pe - $pb + 1);
                $p = $pb;
                $nbr_del++;
            } else {
                $p = $pe +1;
            }
        }

        return $nbr_del;

    }

    function MsWord_CleanRsID(&$Txt) {
        /* Delete XML attributes relative to log of user modifications. Returns the number of deleted attributes.
      In order to insert such information, MsWord does split TBS tags with XML elements.
      After such attributes are deleted, we can concatenate duplicated XML elements. */

        $rs_lst = array('w:rsidR', 'w:rsidRPr');

        $nbr_del = 0;
        foreach ($rs_lst as $rs) {

            $rs_att = ' '.$rs.'="';
            $rs_len = strlen($rs_att);

            $p = 0;
            while ($p!==false) {
                // search the attribute
                $ok = false;
                $p = strpos($Txt, $rs_att, $p);
                if ($p!==false) {
                    // attribute found, now seach tag bounds
                    $po = strpos($Txt, '<', $p);
                    $pc = strpos($Txt, '>', $p);
                    if ( ($pc!==false) && ($po!==false) && ($pc<$po) ) { // means that the attribute is actually inside a tag
                        $p2 = strpos($Txt, '"', $p+$rs_len); // position of the delimiter that closes the attribute's value
                        if ( ($p2!==false) && ($p2<$pc) ) {
                            // delete the attribute
                            $Txt = substr_replace($Txt, '', $p, $p2 -$p +1);
                            $ok = true;
                            $nbr_del++;
                        }
                    }
                    if (!$ok) $p = $p + $rs_len;
                }
            }

        }

        // delete empty tags
        $Txt = str_replace('<w:rPr></w:rPr>', '', $Txt);
        $Txt = str_replace('<w:pPr></w:pPr>', '', $Txt);

        return $nbr_del;

    }

    function MsWord_CleanDuplicatedLayout(&$Txt) {
        // Return the number of deleted dublicates

        $wro = '<w:r';
        $wro_len = strlen($wro);

        $wrc = '</w:r';
        $wrc_len = strlen($wrc);

        $wto = '<w:t';
        $wto_len = strlen($wto);

        $wtc = '</w:t';
        $wtc_len = strlen($wtc);

        $nbr = 0;
        $wro_p = 0;
        while ( ($wro_p=$this->XML_FoundTagStart($Txt, $wro, $wro_p))!==false ) {
            $wto_p = $this->XML_FoundTagStart($Txt,$wto,$wro_p); if ($wto_p===false) return false; // error in the structure of the <w:r> element
            $first = true;
            do {
                $ok = false;
                $wtc_p = $this->XML_FoundTagStart($Txt,$wtc,$wto_p); if ($wtc_p===false) return false; // error in the structure of the <w:r> element
                $wrc_p = $this->XML_FoundTagStart($Txt,$wrc,$wro_p); if ($wrc_p===false) return false; // error in the structure of the <w:r> element
                if ( ($wto_p<$wrc_p) && ($wtc_p<$wrc_p) ) { // if the found <w:t> is actually included in the <w:r> element
                    if ($first) {
                        $superflous = '</w:t></w:r>'.substr($Txt, $wro_p, ($wto_p+$wto_len)-$wro_p); // should be like: '</w:t></w:r><w:r>....<w:t'
                        $superflous_len = strlen($superflous);
                        $first = false;
                    }
                    $x = substr($Txt, $wtc_p+$superflous_len,1);
                    if ( (substr($Txt, $wtc_p, $superflous_len)===$superflous) && (($x===' ') || ($x==='>')) ) {
                        // if the <w:r> layout is the same same the next <w:r>, then we join it
                        $p_end = strpos($Txt, '>', $wtc_p+$superflous_len); //
                        if ($p_end===false) return false; // error in the structure of the <w:t> tag
                        $Txt = substr_replace($Txt, '', $wtc_p, $p_end-$wtc_p+1);
                        $nbr++;
                        $ok = true;
                    }
                }
            } while ($ok);

            $wro_p = $wro_p + $wro_len;

        }

        return $nbr; // number of replacements

    }

    function MsWord_CleanSpacePreserve(&$Txt) {
        // apply xml:space="preserve" by default for the entire document
        // unfotunately, it doesn't work on headers (<w:hdr>) and footers (<w:ftr>)
        $p = $this->XML_FoundTagStart($Txt, '<w:document', 0);
        if ($p===false) return;
        $pe = strpos($Txt, '>', $p);
        $x = substr($Txt, $p, $pe-$p+1);
        if (strpos($x, 'xml:space=')===false) {
            // insert the default value
            $Txt = substr_replace($Txt, ' xml:space="preserve"', $pe, 0);
            $Txt = str_replace('<w:t xml:space="preserve">', '<w:t>', $Txt); // not obligatory but cleanner and save space
        }
    }

    function MsWord_RenumDocPr() {
        /* Renumber attribute "id " of elements <wp:docPr> in order to ensure unicity.
         Such elements are used in objects.
      */

        $file = $this->ExtInfo['main'];
        $idx = $this->FileGetIdx($file);
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'Word renume DocPr ids');
        if ($Txt===false) return false;

        $el = '<wp:docPr ';
        $el_len = strlen($el);

        $id = ' id="';
        $id_len = strlen($id);

        $nbr = 0;
        $last_id = 0;

        $p = 0;
        while ($p!==false) {
            // search the element
            $p = strpos($Txt, $el, $p);
            if ($p!==false) {
                // attribute found, now seach tag bounds
                $p = $p + $el_len -1; // don't take the space, it is used for the next search
                $pc = strpos($Txt, '>', $p);
                if ($pc!==false) {
                    $x = substr($Txt, $p, $pc - $p);
                    $pi = strpos($x, $id);
                    if ($pi!==false) {
                        $pi = $pi + $id_len;
                        $pq = strpos($x, '"', $pi);
                        if ($pq!==false) {
                            $i_len = $pq - $pi;
                            $i = intval(substr($x, $pi, $i_len));
                            if ($i>0) { // id="0" is erroneous
                                if ($i>$last_id) {
                                    $last_id = $i; // nothing else to do
                                } else {
                                    $last_id++;
                                    $id_txt = ''.$last_id;
                                    $Txt = substr_replace($Txt, $id_txt, $p + $pi, $i_len);
                                    $nbr++;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($nbr>0) $this->TbsStorePut($idx, $Txt);

        return $nbr;

    }

    // OpenOffice documents

    function OpenDoc_ManifestChange($Path, $Type) {
        // Set $Type=false in order to mark the the manifest entry to be deleted.
        // Video and sound files are not to be registered in the manifest since the contents is not saved in the document.

        // Initialization
        if (!isset($this->OpenDocManif)) $this->OpenDocManif = array();

        // We try to found the type of image
        if (($Type==='') && (substr($Path,0,9)==='Pictures/')) {
            $ext = basename($Path);
            $p = strrpos($ext, '.');
            if ($p!==false) {
                $ext = strtolower(substr($ext,$p+1));
                if (isset($this->ExtInfo['pic_ext'][$ext])) $Type = 'image/'.$this->ExtInfo['pic_ext'][$ext];
            }
        }

        $this->OpenDocManif[$Path] = $Type;

    }

    function OpenDoc_ManifestCommit($Debug) {

        // Retrieve the content of the manifest
        $name = 'META-INF/manifest.xml';
        $idx = $this->FileGetIdx($name);
        if ($idx===false) return;

        $Txt = $this->TbsStoreGet($idx, 'OpenDocumentFormat');
        if ($Txt===false) return false;

        // Perform all changes
        foreach ($this->OpenDocManif as $Path => $Type) {
            $x = 'manifest:full-path="'.$Path.'"';
            $p = strpos($Txt,$x);
            if ($Type===false) {
                // the entry should be deleted
                if ($p!==false) {
                    $p1 = strrpos(substr($Txt,0,$p), '<');
                    $p2 = strpos($Txt,'>',$p);
                    if (($p1!==false) && ($p2!==false)) $Txt = substr($Txt,0,$p1).substr($Txt,$p2+1);
                }
            } else {
                // the entry should be added
                if ($p===false) {
                    $p = strpos($Txt,'</manifest:manifest>');
                    if ($p!==false) {
                        $x = ' <manifest:file-entry manifest:media-type="'.$Type.'" '.$x.'/>'."\n";
                        $Txt = substr_replace($Txt, $x, $p, 0);
                    }
                }
            }
        }

        // Save changes (no need to save it in the park because this fct is called after merging)
        $this->FileReplace($idx, $Txt);

        if ($Debug) $this->DebugLst[$name] = $Txt;

    }

    function OpenDoc_ChangeCellType(&$Txt, &$Loc, $Ope, $IsMerging, &$Value) {
        // change the type of a cell in an ODS file

        $Loc->PrmLst['odsok'] = true; // avoid the field to be processed twice

        if ($Ope==='odsStr') return true;

        static $OpeLst = array('odsNum'=>'float', 'odsPercent'=>'percentage', 'odsCurr'=>'currency', 'odsBool'=>'boolean', 'odsDate'=>'date', 'odsTime'=>'time');
        $AttStr = 'office:value-type="string"';
        $AttStr_len = strlen($AttStr);

        if (!isset($OpeLst[$Ope])) return false;

        $t0 = clsTinyButStrong::f_Xml_FindTagStart($Txt, 'table:table-cell', true, $Loc->PosBeg, false, true);
        if ($t0===false) return false; // error in the XML structure

        $te = strpos($Txt, '>', $t0);
        if ( ($te===false) || ($te>$Loc->PosBeg) ) return false; // error in the XML structure

        $len = $te - $t0 + 1;
        $tag = substr($Txt, $t0, $len);

        $p = strpos($tag, $AttStr);
        if ($p===false) return false; // error: the cell was expected to have a string contents since it contains a TBS tag.

        // replace the current string with blanck chars
        $len = $Loc->PosEnd - $Loc->PosBeg + 1;
        $Txt = substr_replace($Txt, str_repeat(' ',$len), $Loc->PosBeg, $len);

        // prepare special formating for the value
        $type = $OpeLst[$Ope];
        $att_new = 'office:value-type="'.$type.'"';
        $newfrm = false;
        switch ($type) {
            case 'float':      $att_new .= ' office:value="[]"'; break;
            case 'percentage': $att_new .= ' office:value="[]"'; break;
            case 'currency':   $att_new .= ' office:value="[]"'; if (isset($Loc->PrmLst['currency'])) $att_new .= ' office:currency="'.$Loc->PrmLst['currency'].'"'; break;
            case 'boolean':    $att_new .= ' office:boolean-value="[]"'; break;
            case 'date':       $att_new .= ' office:date-value="[]"'; $newfrm = 'yyyy-mm-ddThh:nn:ss'; break;
            case 'time';       $att_new .= ' office:time-value="[]"'; $newfrm = '"PT"hh"H"nn"M"ss"S"'; break;
        }

        // replace the sring attribute with the new attribute
        //$diff = strlen($att_new) - $AttStr_len;
        $p_att = $t0 + $p;
        $p_fld = $p_att + strpos($att_new, '['); // new position of the fields in $Txt
        $Txt = substr_replace($Txt, $att_new, $p_att, $AttStr_len);

        // move the TBS field
        $Loc->PosBeg = $p_fld;
        $Loc->PosEnd = $p_fld +1;

        if ($IsMerging) {
            // the field is currently beeing merged
            if ($type==='boolean') {
                if ($Value) {
                    $Value = 'true';
                } else {
                    $Value = 'false';
                }
            } elseif ($newfrm!==false) {
                $prm = array('frm'=>$newfrm);
                $Value = $this->TBS->meth_Misc_Format($Value,$prm);
            }
            $Loc->ConvStr = false;
            $Loc->ConvProtect = false;
        } else {
            if ($newfrm!==false) $Loc->PrmLst['frm'] = $newfrm;
        }

    }

    function OpenDoc_SheetInit($force = false) {

        if (isset($this->OpenDoc_Sheets) && (!$force) ) return;

        $this->OpenDoc_Sheets = array();     // sheet info sorted by location

        $idx = $this->FileGetIdx($this->ExtInfo['main']);
        if ($idx===false) return;
        $Txt = $this->TbsStoreGet($idx, 'Sheet Info');
        if ($Txt===false) return false;
        if ($this->LastReadNotStored) $this->TbsStorePut($idx, $Txt);
        $this->OpenDoc_Sheets_FileId = $idx;

        // scann sheet list
        $p = 0;
        $idx = 0;
        while ($loc=clsTinyButStrong::f_Xml_FindTag($Txt, 'table:table', true, $p, true, false, true, true) ) {
            $this->OpenDoc_Sheets[$idx] = $loc;
            $idx++;
            $p = $loc->PosEnd;
        }

    }

    function OpenDoc_SheetDeleteAndDisplay() {

        if (!isset($this->OtbsSheetOk)) return;
        if ( (count($this->OtbsSheetDelete)==0) && (count($this->OtbsSheetVisible)==0) ) return;

        $this->OpenDoc_SheetInit(true);
        $Txt = $this->TbsStoreGet($this->OpenDoc_Sheets_FileId, 'Sheet Delete and Display');

        $close = '</table:table>';
        $close_len = strlen($close);

        $styles_to_edit = array();
        // process sheet in rever order of their positions
        for ($idx = count($this->OpenDoc_Sheets) - 1; $idx>=0; $idx--) {
            $loc = $this->OpenDoc_Sheets[$idx];
            $id = 'i:'.($idx + 1);
            $name = 'n:'.$loc->PrmLst['table:name'];
            if ( isset($this->OtbsSheetDelete[$name]) || isset($this->OtbsSheetDelete[$id]) ) {
                // Delete the sheet
                $p = strpos($Txt, $close, $loc->PosEnd);
                if ($p===false) return; // XML error
                $Txt = substr_replace($Txt, '', $loc->PosBeg, $p + $close_len - $loc->PosBeg);
                unset($this->OtbsSheetDelete[$name]);
                unset($this->OtbsSheetDelete[$id]);
                unset($this->OtbsSheetVisible[$name]);
                unset($this->OtbsSheetVisible[$id]);
            } elseif ( isset($this->OtbsSheetVisible[$name]) || isset($this->OtbsSheetVisible[$id]) ) {
                // Hide or dispay the sheet
                $visible = (isset($this->OtbsSheetVisible[$name])) ? $this->OtbsSheetVisible[$name] : $this->OtbsSheetVisible[$id];
                $visible = ($visible) ? 'true' : 'false';
                if (isset($loc->PrmLst['table:style-name'])) {
                    $style = $loc->PrmLst['table:style-name'];
                    $new = $style.'_tbs_'.$visible;
                    if (!isset($styles_to_edit[$style])) $styles_to_edit[$style] = array();
                    $styles_to_edit[$style][$visible] = $new; // mark the style to be edited
                    $pi = $loc->PrmPos['table:style-name'];
                    $Txt = substr_replace($Txt, $pi[4].$new.$pi[4], $pi[2], $pi[3]-$pi[2]);
                }
                unset($this->OtbsSheetVisible[$name]);
                unset($this->OtbsSheetVisible[$id]);
            }
        }

        // process styles to edit
        if (count($styles_to_edit)>0) {
            $close = '</style:style>';
            $close_len = strlen($close);
            $p = 0;
            while ($loc=clsTinyButStrong::f_Xml_FindTag($Txt, 'style:style', true, $p, true, false, true, false) ) {
                $p = $loc->PosEnd;
                if (isset($loc->PrmLst['style:name'])) {
                    $name = $loc->PrmLst['style:name'];
                    if (isset($styles_to_edit[$name])) {
                        // retrieve the full source of the <style:style> element
                        $p = strpos($Txt, $close, $p);
                        if ($p===false) return; // bug in the XML contents
                        $p = $p + $close_len;
                        $src = substr($Txt, $loc->PosBeg, $p - $loc->PosBeg);
                        // add the attribute, if missing
                        if (strpos($src, ' table:display="')===false)  $src = str_replace('<style:table-properties ', '<style:table-properties table:display="true" ', $src);
                        // add new styles
                        foreach ($styles_to_edit[$name] as $visible => $newName) {
                            $not = ($visible==='true') ? 'false' : 'true';
                            $src2 = str_replace(' style:name="'.$name.'"', ' style:name="'.$newName.'"', $src);
                            $src2 = str_replace(' table:display="'.$not.'"', ' table:display="'.$visible.'"', $src2);
                            $Txt = substr_replace($Txt, $src2, $loc->PosBeg, 0);
                            $p = $p + strlen($src2);
                        }
                    }
                }
            }

        }

        // store the result
        $this->TbsStorePut($this->OpenDoc_Sheets_FileId, $Txt);

        $this->TbsSheetCheck();

    }

    function OpenDoc_SheetDebug($nl, $sep, $bull) {

        $this->OpenDoc_SheetInit();

        echo $nl;
        echo $nl."Sheets in the Workbook:";
        echo $nl."-----------------------";
        foreach ($this->OpenDoc_Sheets as $idx => $loc) {
            $name = str_replace(array('&amp;','&quot;','&lt;','&gt;'), array('&','"','<','>'), $loc->PrmLst['table:name']);
            echo $bull."id: ".($idx+1).", name: [".$name."]";
        }

    }

}