<?
sdk_header('text/xml');

$username = getArg('username', true);
$password = getArg('password', true);
$masterCode = getArg('mastercode', true);
$systemName = getArg('systemname', true);
$action = getArg('action', true); // $action vaut 0 pour �teindre l'alarme, ou 100 pour l'allumer, ou 'state' pour savoir le status

// restitution du r�sultat au format xml
// $result est un array avec ["state" => "off", "groups" => ""]
function sdk_showResult($result, $error=null) {
  // on se d�logue
  httpQuery("https://appv3.tt-monitor.com/topaze/authenticate/logout", "POST", '{"systemId":"null"}', null, array("Content-Type: application/json", "Authorization: Bearer ".$sessionId, "X-Identity-Provider: JANRAIN", "ttmSessionIdNotRequired: true"));

  // on �crit le contenu du XML retourn�
  echo "<root>";
  echo "  <diagral>";
  if ($error !== null) {
    echo "    <error>".$error."</error>";
  } else {
    echo "    <status>".$result["state"]."</status>";
    echo "    <groups>".$result["groups"]."</groups>";
  }
  echo "  </diagral>";
  echo "</root>";
}

// on se logue
$resStr = httpQuery("https://appv3.tt-monitor.com/topaze/authenticate/login", "POST", '{"username":"'.$username.'", "password":"'.$password.'"}', null, array("Content-Type: application/json"));
$res = sdk_json_decode($resStr, true);
if (!isset($res["sessionId"])) {
  if ($res["message"] == "error.connect.mydiagralusernotfound") {
    sdk_showResult(null, "Utilisateur �".$username."� ou password �".$password."� inconnu : ".$resStr);
  } else {
    sdk_showResult(null, "'sessionId' n'est pas contenu dans la r�pose : ".$resStr);
  }
  return;
}
$sessionId = $res['sessionId'];
$diagralId = $res['diagralId'];

// on r�cup�re tous les syst�mes
$systems = httpQuery("https://appv3.tt-monitor.com/topaze/configuration/getSystems", "POST", '{}', null, array("Content-Type: application/json", "Authorization: Bearer ".$sessionId, "X-Identity-Provider: JANRAIN", "ttmSessionIdNotRequired: true"));
$systems = sdk_json_decode($systems, true);

// on regarde si le syst�me demand� est l�
$systemToUse = null;
$systemNames = array();
foreach($systems['systems'] as $system) {
  $systemNames[] = $system["name"];
  if ($system["name"] === $systemName) {
    $systemToUse = $system;
    break;
  }
}
// si on n'a pas trouv� le syst�me indiqu�
if ($systemToUse === null) {
  sdk_showResult(null, "Le syst�me nomm� � ".$systemName." � n'a pas �t� trouv� parmi ".implode(" / ", $systemNames));
  return;
}

// on r�cup�re la configuration
$config = httpQuery("https://appv3.tt-monitor.com/topaze/configuration/getConfiguration", "POST", '{"systemId":'.$systemToUse["id"].',"role":'.$systemToUse["role"].'}', null, array("Content-Type: application/json", "Authorization: Bearer ".$sessionId, "X-Identity-Provider: JANRAIN", "ttmSessionIdNotRequired: true"));
$config = sdk_json_decode($config, true);

// on v�rifie les droits de l'utilisateurs
if ($systemToUse["role"] == 0 && !$config["rights"]["UNIVERSE_ALARMS"]) {
 sdk_showResult(null, "Le compte utilis� n'a pas les droits sur l'alarme.");
 return;
}
$transmitterId = $config["transmitterId"];
$centralId = $data["centralId"];

// on se connecte avec le mastercode
$connectStr = httpQuery("https://appv3.tt-monitor.com/topaze/authenticate/connect", "POST", '{"masterCode":"'.$masterCode.'","transmitterId":"'.$transmitterId.'","systemId":'.$systemToUse["id"].',"role":'.$systemToUse["role"].'}', null, array("Content-Type: application/json", "Authorization: Bearer ".$sessionId, "X-Identity-Provider: JANRAIN", "ttmSessionIdNotRequired: true"));
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
      sdk_showResult(null, "Probl�me avec la session.");
      return;
    }
    default: {
      sdk_showResult(null, "'ttmSessionId' n'est pas pr�sent dans ce qu'a retourn� le serveur : ". $connectStr);
      return;
    }
  }
}

if (is_numeric($action) && ($action == 0 || $action == 100)) {
  // on active/d�sactive l'alarme
  $systemStateStr = httpQuery("https://appv3.tt-monitor.com/topaze/action/stateCommand", "POST", '{"systemState":"'.($action==100?"on":"off").'","group":[],"currentGroup":[],"nbGroups":"4","ttmSessionId":"'.$ttmSessionId.'"}', null, array("Content-Type: application/json", "Authorization: Bearer ".$sessionId, "X-Identity-Provider: JANRAIN", "ttmSessionIdNotRequired: true"));
  $systemState = sdk_json_decode($systemStateStr, true);

  if(!isset($systemState["commandStatus"]) || $systemState["commandStatus"] !== "CMD_OK") {
    sdk_showResult(null, $systemStateStr);
    return;
  }
}

// on retourne l'�tat de l'alarme
$alarmStatusStr = httpQuery("https://appv3.tt-monitor.com/topaze/status/getSystemState", "POST", '{"centralId":"'.$centralId.'","ttmSessionId":"'.$ttmSessionId.'"}', null, array("Content-Type: application/json", "Authorization: Bearer ".$sessionId, "X-Identity-Provider: JANRAIN", "ttmSessionIdNotRequired: true"));
$alarmStatus = sdk_json_decode($alarmStatusStr, true);
$systemState = "inconnu";
if(isset($alarmStatus["systemState"])) {
  // le statut peut �tre "off", "group", "tempogroup", "presence", ou "on"
  $systemState = $alarmStatus["systemState"];
  $groups = implode(" / ", $alarmStatus["groups"]);
  sdk_showResult(array("state" => $systemState, "groups" => $groups));
  return;
} else {
  switch ($alarmStatus["message"]) {
    case "transmitter.error.invalidsessionid": {
      sdk_showResult(null, "Probl�me avec la session.");
      return;
    }
    default:{
      sdk_showResult(null, "Erreur inconnue");
      return;
    }
  }
}
?>
