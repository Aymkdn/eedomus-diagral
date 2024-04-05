<?
// basé sur https://github.com/mguyard/Jeedom-Diagral_eOne
sdk_header('text/xml');

$username = getArg('username', true);
$password = getArg('password', true);
$masterCode = getArg('mastercode', true);
$systemName = getArg('systemname', true);
$action = getArg('action', true); // $action vaut 0 pour éteindre l'alarme, ou 100 pour l'allumer, ou 'state' pour savoir le status

$defaultHeaders = array(
  "User-Agent: eOne/1.12.1.2 CFNetwork/1240.0.4 Darwin/20.6.0",
  "Accept: application/json, text/plain, */*",
  "Accept-Encoding: deflate",
  "X-App-Version: 1.12.1",
  "X-Identity-Provider: JANRAIN",
  "ttmSessionIdNotRequired: true",
  "X-Vendor: diagral"
);

// restitution du résultat au format xml
// $result est un array avec ["state" => "off", "group" => 1]
function sdk_showResult($result, $error=null) {
  // on se délogue
  httpQuery("https://appv3.tt-monitor.com/topaze/authenticate/logout", "POST", '{"systemId":"null"}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));

  $state = ($result["state"]==="off" ? "off" : "on");
  $label = $result["state"];

  // pour les groupes, on va retourner (tempo)group + n° du groupe
  if ($label === "group" || $label === "tempogroup") {
    $label .= $result["group"];
  }

  switch ($label) {
    case "off": $value=0; break;
    case "on": $value=100; break;
    case "presence": $value=105; break;
    default:{
      if (strpos($label, 'group1') !== false) $value = 101;
      else if (strpos($label, 'group2') !== false) $value = 102;
      else if (strpos($label, 'group3') !== false) $value = 103;
      else if (strpos($label, 'group4') !== false) $value = 104;
    }
  }

  // on écrit le contenu du XML retourné
  echo "<root>";
  echo "  <diagral>";
  if ($error !== null) {
    echo "    <error>".str_replace(array("é", "è"), array("&eacute;", "&egrave;"), $error)."</error>";
  } else {
    echo "    <state>".$state."</state>";
    echo "    <label>".$label."</label>";
    echo "    <value>".$value."</value>";
  }
  echo "  </diagral>";
  echo "</root>";
}

// source: https://github.com/mguyard/Jeedom-Diagral_eOne/blob/master/3rparty/Diagral-eOne-API-PHP/class/Diagral/UUID.class.php
function sdk_uuid() {
  return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    // 32 bits for "time_low"
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    // 16 bits for "time_mid"
    mt_rand(0, 0xffff),
    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 4
    mt_rand(0, 0x0fff) | 0x4000,
    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1
    mt_rand(0, 0x3fff) | 0x8000,
    // 48 bits for "node"
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );
}

// on se logue
$resStr = httpQuery("https://appv3.tt-monitor.com/topaze/authenticate/login", "POST", '{"username":"'.$username.'", "password":"'.$password.'"}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8")));
$res = sdk_json_decode($resStr, true);
if (!isset($res["sessionId"])) {
  if ($res["message"] == "error.connect.mydiagralusernotfound") {
    sdk_showResult(null, "Utilisateur «".$username."» ou password «".$password."» inconnu : ".$resStr);
  } else {
    sdk_showResult(null, "'sessionId' n'est pas contenu dans la répose : ".$resStr);
  }
  return;
}
$sessionId = $res['sessionId'];
$diagralId = $res['diagralId'];

// on récupère tous les systèmes
$systems = httpQuery("https://appv3.tt-monitor.com/topaze/configuration/getSystems", "POST", '{}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));
$systems = sdk_json_decode($systems, true);

// on regarde si le système demandé est là
$systemToUse = null;
$systemNames = array();
foreach($systems['systems'] as $system) {
  $systemNames[] = $system["name"];
  if ($system["name"] === $systemName) {
    $systemToUse = $system;
    break;
  }
}
// si on n'a pas trouvé le système indiqué
if ($systemToUse === null) {
  sdk_showResult(null, "Le système nommé « ".$systemName." » n'a pas été trouvé parmi ".implode(" / ", $systemNames));
  return;
}

// on récupère la configuration
$config = httpQuery("https://appv3.tt-monitor.com/topaze/configuration/getConfiguration", "POST", '{"systemId":'.$systemToUse["id"].',"role":'.$systemToUse["role"].'}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));
$config = sdk_json_decode($config, true);

// on vérifie les droits de l'utilisateurs
if ($systemToUse["role"] == 0 && !$config["rights"]["UNIVERSE_ALARMS"]) {
 sdk_showResult(null, "Le compte utilisé n'a pas les droits sur l'alarme.");
 return;
}
$transmitterId = $config["transmitterId"];
$centralId = $config["centralId"];

// on se connecte avec le mastercode
$connectStr = httpQuery("https://appv3.tt-monitor.com/topaze/authenticate/connect", "POST", '{"masterCode":"'.$masterCode.'","transmitterId":"'.$transmitterId.'","systemId":'.$systemToUse["id"].',"role":'.$systemToUse["role"].'}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));
$connect = sdk_json_decode($connectStr, true);

if(isset($connect["ttmSessionId"])) {
  $ttmSessionId = $connect["ttmSessionId"];
  $systemState = $connect["systemState"];
  $groups = $connect["groups"];
  $versions = $connect["versions"];
} else {
  // on a une erreur
  switch ($connect["message"]) {
    case 'transmitter.connection.badpincode': {
      sdk_showResult(null, "Le master code ".$masterCode." est incorrect.");
      return;
    }
    case "transmitter.connection.sessionalreadyopen": {
      sdk_showResult(null, "Problème avec la session.");
      return;
    }
    default: {
      sdk_showResult(null, "'ttmSessionId' n'est pas présent dans ce qu'a retourné le serveur : ". $connectStr);
      return;
    }
  }
}

// si on veut lister les différents scenarios
if($action === "scenarios") {
  $uuid = sdk_uuid();
  $try=0;
  $devicesStr = httpQuery("https://appv3.tt-monitor.com/topaze/configuration/v2/getDevicesMultizone/".$uuid, "POST", '{"systemId":"'.$systemToUse["id"].'","centralId":"'.$centralId.'","transmitterId":"'.$transmitterId.'","ttmSessionId":"'.$ttmSessionId.'","isVideoOptional":"true","isScenariosZoneOptional":"true","boxVersion":"'.$versions["box"].'"}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)), false, true);
  do {
    $devicesStatusStr = httpQuery("https://appv3.tt-monitor.com/topaze/configuration/v2/getDevicesMultizone/".$uuid, "GET", null, null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)), false, true);
    $devicesStatus = sdk_json_decode($devicesStatusStr, true);
    if ($devicesStatus["status"] === "request_status_done") {
      // on est obligé de faire un traitement sur la réponse sinon sdk_json_decode ne fonctionne pas
      $response = str_replace("'","",$devicesStatus["response"]);
      $scenarios = substr($response, strpos($response, '"manualOrEventScenarios"')-1);
      $scenarios = substr($scenarios, 0, strpos($scenarios, ',"rawData"'))."}";
      $devices = sdk_json_decode($scenarios, true);
      echo "<root>";
      echo "  <diagral>";
      foreach ($devices["manualOrEventScenarios"] as $scenario) {
        echo "    <scenario>";
        echo "      <id>".$scenario["id"]."</id>";
        echo "      <name>".$scenario["name"]."</name>";
        echo "    </scenario>";
      }
      echo "  </diagral>";
      echo "</root>";
      break;
    }
  } while(++$try < 500);
} else {
  if (is_numeric($action)) {
    if (($action == 0 || $action >= 100) && $action < 1000) {
      // on active/désactive l'alarme
      $state = "off";
      $group = "";
      switch($action) {
        case 100: $state="on"; break;
        case 101: $state="group"; $group=1; break;
        case 102: $state="group"; $group=2; break;
        case 103: $state="group"; $group=3; break;
        case 104: $state="group"; $group=4; break;
        case 105: $state="presence"; break;
      }
      $systemStateStr = httpQuery("https://appv3.tt-monitor.com/topaze/action/stateCommand", "POST", '{"systemState":"'.$state.'","group":['.$group.'],"currentGroup":[],"nbGroups":"4","ttmSessionId":"'.$ttmSessionId.'"}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));
      $systemState = sdk_json_decode($systemStateStr, true);

      if(!isset($systemState["commandStatus"]) || $systemState["commandStatus"] !== "CMD_OK") {
        sdk_showResult(null, $systemStateStr);
        return;
      }
    } else if ($action > 1000) {
      // on déclenche un scénario
      $id = $action - 1000;
      $scenarioStr = httpQuery("https://appv3.tt-monitor.com/topaze/api/scenarios/launch", "POST", '{"scenarioId":"'.$id.'","ttmSessionId":"'.$ttmSessionId.'"}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));
      
      if ($scenarioStr !== '["CMD_OK"]') {
        sdk_showResult(null, $scenarioStr);
        return;
      }
    }
  }

  // on retourne l'état de l'alarme
  $alarmStatusStr = httpQuery("https://appv3.tt-monitor.com/topaze/status/getSystemState", "POST", '{"centralId":"'.$centralId.'","ttmSessionId":"'.$ttmSessionId.'"}', null, array_merge($defaultHeaders, array("Content-Type: application/json;charset=UTF-8", "Authorization: Bearer ".$sessionId)));
  $alarmStatus = sdk_json_decode($alarmStatusStr, true);
  $systemState = "inconnu";
  if(isset($alarmStatus["systemState"])) {
    // le statut peut être "off", "group", "tempogroup", "presence", ou "on"
    $systemState = $alarmStatus["systemState"];
    // si on a plusieurs groupes activés, on va considérer que toute la maison est en marche
    $group = "";
    if (count($alarmStatus["groups"]) > 1) {
      $systemState = "on";
    } else if (count($alarmStatus["groups"]) === 1) {
      // si on a qu'un groupe, on l'affiche
      $group = $alarmStatus["groups"][0];
    }
    sdk_showResult(array("state" => $systemState, "group" => $group));
    return;
  } else {
    switch ($alarmStatus["message"]) {
      case "transmitter.error.invalidsessionid": {
        sdk_showResult(null, "Problème avec la session.");
        return;
      }
      default:{
        sdk_showResult(null, "Erreur inconnue");
        return;
      }
    }
  }
}
?>
