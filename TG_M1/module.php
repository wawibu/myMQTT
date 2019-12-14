<?php

class myMQTT extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->BufferResponse = '';
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        //Anzahl die in der Konfirgurationsform angezeigt wird - Hier Standard auf 1
        $this->RegisterPropertyString('Topic', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->BufferResponse = '';
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');
        //Setze Filter fuer ReceiveData

        $this->SendDebug(__FUNCTION__ . ' FullTopic', $this->ReadPropertyString('Topic'), 0);
        $topic = $this->FilterFullTopicReceiveData();
        $this->SendDebug(__FUNCTION__ . ' Filter FullTopic', $topic, 0);

        $this->SetReceiveDataFilter('.*' . $topic . '.*');
    }

    public function ReceiveData($JSONString)
    {
//        $this->SendDebug('JSON', $JSONString, 0);
        if (!empty($this->ReadPropertyString('Topic'))) {
//            $this->SendDebug('ReceiveData JSON', $JSONString, 0);
            $data = json_decode($JSONString);
            // Buffer decodieren und in eine Variable schreiben
            $Buffer = $data;
//            $this->SendDebug('Topic', $Buffer->Topic, 0);
//            $this->SendDebug('Payload', $Buffer->Payload, 0);
            $this->SendDebug('Topic / Payload', $Buffer->Topic." >>>> ".$Buffer->Payload, 0);
            $jsonExplode = explode('/', $Buffer->Topic);

            $InstanceID_p = $this->InstanceID;

            $mainTopic = $this->ReadPropertyString('Topic');
            $TopicCount = count($jsonExplode) - 1;
            
            foreach($jsonExplode as $keyNumber=>$InstanceName) {
//                $this->SendDebug("schleife","keyNumber: ".$keyNumber." >> InstanceName: ".$InstanceName." >> InstanceID_p: ".$InstanceID_p,0);
                if($keyNumber == $TopicCount) { 
                    $VarName = $InstanceName;
                    continue; 
                }
            
                if ($InstanceName != $mainTopic) { 
                    $InstanceID = @IPS_GetInstanceIDByName($InstanceName, $InstanceID_p);
                    if (!$InstanceID) {
                        $InstanceID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
                        IPS_SetName($InstanceID, $InstanceName);
                        IPS_SetParent($InstanceID, $InstanceID_p);
                    }
                    $InstanceID_p = $InstanceID;
                }
            }
			
			$jsonDecodePayload = json_decode($Buffer->Payload,true);
			$this->SendDebug('is Array', is_array($jsonDecodePayload), 0);
				
			if(is_array($jsonDecodePayload)) { 
				$InstanceName = $VarName;
				$InstanceID = @IPS_GetInstanceIDByName($InstanceName, $InstanceID_p);
				if (!$InstanceID) {
					$InstanceID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
					IPS_SetName($InstanceID, $InstanceName);
					IPS_SetParent($InstanceID, $InstanceID_p);
				}
				$InstanceID_p = $InstanceID;			
				foreach($jsonDecodePayload as $PayloadKey => $PayloadValue) {
					$this->SendDebug($PayloadKey, $PayloadValue, 0);
					$VarName = $PayloadKey;
					$VarValue = $PayloadValue;
					$VarID = @IPS_GetVariableIDbyName($VarName,$InstanceID_p);
					if (!$VarID) {
						if(strtoupper($VarValue) == 'TRUE' or strtoupper($VarValue) == 'FALSE') {
							$this->RegisterVariableBoolean($InstanceName.$VarName.'Bool', $VarName, '', 0);
						}
						elseif (is_numeric($VarValue)) 	{ 
							if (!strpos($VarValue,".")) {
								$this->RegisterVariableInteger($InstanceName.$VarName.'Int', $VarName, '', 0); 
							} else {
								$this->RegisterVariableFloat($InstanceName.$VarName.'Float', $VarName, '', 0); 
							}
						}
						else { 
							$this->RegisterVariableString($InstanceName.$VarName.'String', $VarName, '', 0); 
						}
						$InstanceID = $this->InstanceID;
						$VarID = @IPS_GetVariableIDbyName($VarName, $InstanceID );
						IPS_SetParent($VarID, $InstanceID_p);
					} 
					SetValue($VarID, $VarValue);					
				}
			} else
			{
				$VarValue = $Buffer->Payload;
				$VarID = @IPS_GetVariableIDbyName($VarName,$InstanceID_p);
				if (!$VarID) {
					if(strtoupper($VarValue) == 'TRUE' or strtoupper($VarValue) == 'FALSE') {
						$this->RegisterVariableBoolean($InstanceName.$VarName.'Bool', $VarName, '', 0);
					}
					elseif (is_numeric($VarValue)) 	{ 
						if (!strpos($VarValue,".")) {
							$this->RegisterVariableInteger($InstanceName.$VarName.'Int', $VarName, '', 0); 
						} else {
							$this->RegisterVariableFloat($InstanceName.$VarName.'Float', $VarName, '', 0); 
						}
					}
					else { 
						$this->RegisterVariableString($InstanceName.$VarName.'String', $VarName, '', 0); 
					}
					$InstanceID = $this->InstanceID;
					$VarID = @IPS_GetVariableIDbyName($VarName, $InstanceID );
					IPS_SetParent($VarID, $InstanceID_p);
				} 
				SetValue($VarID, $VarValue);
			}
/*
            //IrReceived
            if (fnmatch('*IrReceived*', $Buffer->Payload)) {
                $myBuffer = json_decode($Buffer->Payload);
                $this->SendDebug('IrReceived', $Buffer->Payload, 0);
                if (property_exists($myBuffer->IrReceived, 'Protocol')) {
                    $this->RegisterVariableString('Tasmota_IRProtocol', 'IR Protocol', '', 0);
                    SetValue($this->GetIDForIdent('Tasmota_IRProtocol'), $myBuffer->IrReceived->Protocol);
                }
                if (property_exists($myBuffer->IrReceived, 'Bits')) {
                    $this->RegisterVariableString('Tasmota_IRBits', 'IR Bits', '', 0);
                    SetValue($this->GetIDForIdent('Tasmota_IRBits'), $myBuffer->IrReceived->Bits);
                }
                if (property_exists($myBuffer->IrReceived, 'Data')) {
                    $this->RegisterVariableString('Tasmota_IRData', 'IR Data', '', 0);
                    SetValue($this->GetIDForIdent('Tasmota_IRData'), $myBuffer->IrReceived->Data);
                }
            }
*/
        }
    }

    public function RequestAction($Ident, $Value)
    {
        $this->SendDebug(__FUNCTION__ . ' Ident', $Ident, 0);
        $this->SendDebug(__FUNCTION__ . ' Value', $Value, 0);
        if ($Ident == 'Tasmota_FanSpeed') {
            $result = $this->setFanSpeed($Value);
            return true;
        }

        if (strlen($Ident) != 13) {
            $power = substr($Ident, 13);
        } else {
            $power = 0;
        }
        $result = $this->setPower($power, $Value);
    }

    protected function FilterFullTopicReceiveData()
    {
        $FullTopic = explode('/', $this->ReadPropertyString('Topic'));
        $PrefixIndex = array_search('%prefix%', $FullTopic);
        $TopicIndex = array_search('%topic%', $FullTopic);

        $SetCommandArr = $FullTopic;
        $SetCommandArr[$PrefixIndex] = '.*.';
        //unset($SetCommandArr[$PrefixIndex]);
        $SetCommandArr[$TopicIndex] = $this->ReadPropertyString('Topic');
        $topic = implode('\/', $SetCommandArr);

        return $topic;
    }

    
}
