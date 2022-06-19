# Pré-Requis

Il vous faut une alarme Diagral connectée et utilisable avec l'application e-ONE.

# Installation

Installer le plugin depuis le store Eedomus.

## Configuration

Lors de l'installation, un nouvel appareil `Diagral Alarme` est créé, et on vous demande de remplir plusieurs paramètres :

![Champs de configuration](https://user-images.githubusercontent.com/946315/167384621-0ce5b79f-14e8-49ab-a23f-cd7306e97781.png)

`username` et `password` sont ceux utilisés pour vous connecter à l'application e-ONE. Si vous avez un `&` dans votre password, alors remplacez le par `%26`.

`mastercode` est votre code de sécurité à 4 chiffres que vous utilisez pour gérer la centrale.

`systemname` est le nom du système tel que défini dans l'application e-ONE quand vous allez dans `Paramétrage`, puis dans `Gérer mon profil et mes accès`, puis `Changer le nom du système`.

# Utilisation

Une fois l'appareil créé, vous pouvez aller voir dans l'onglet **Valeurs** :

![Liste des valeurs](https://user-images.githubusercontent.com/946315/173418136-e210bdb8-3a2e-4571-88ca-795224d41a0d.png)

Plusieurs valeurs sont possibles :   
  - `0` pour `Off` (éteindre l'alarme) qui correspond à la désactivation totale de l'alarme ;    
  - `100` pour `On` (allumer l'alarme) qui correspond à l'activation totale de l'alarme ;    
  - de `101` à `104` (allumer un groupe de 1 à 4) qui correspond à l'activation d'un groupe ;    
  - `105` (allumer la présence) qui correspond à l'activation du mode présence.

L'édition de la celulle "Paramètres" va donner quelque chose comme ça :
> &username=abcdef@something.com&password=votremotdepasse&mastercode=1234&systemname=Maison&action=[RAW_VALUE]

## Utilisation avec Alexa

Pour activer/désactive l'alarme à la voix, vous pouvez aller dans `Configurer` de votre box eedomus, puis dans la section de votre assistant vocal, activez les deux cases pour l'appareil : 

![éteint et allume sont cochés sous l'appareil Alarme](https://user-images.githubusercontent.com/946315/167385335-cff26b42-5366-46e6-b0bd-6a21ac7d49c6.png)

Donnez le même nom aux deux. Ici j'ai mis "Alarme".

J'ai testé avec Alexa, et cette opération m'a permis d'avoir un objet "Alarme" que je peux déclencher à la voix avec _« allume l'alarme »_.

## Utilisation avec Google

L'astuce décrite ci-dessus pour Alexa ne semble pas fonctionner correctement avec Google. Pour celui-ci, il faudra plutôt se tourner vers [IFTTT](https://ifttt.com) et faire un appel à l'API de la box eedomus.

## Retour d'état

Le retour d'état se fait directement sur l'appareil.

On peut aussi appeler l'URL [http://localhost/script/?exec=diagral.php&username=USERNAME&password=PASSWORD&mastercode=MASTERCODE&systemname=SYSTEMNAME&action=state](http://localhost/script/?exec=diagral.php&username=USERNAME&password=PASSWORD&mastercode=MASTERCODE&systemname=SYSTEMNAME&action=state) pour avoir le fichier XML ci-dessous :    
> &lt;root>   
> &nbsp;&nbsp;&lt;diagral>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;error>message d'erreur (s'il existe)&lt;/error>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;state>le statut de l'alarme (soit 'on', soit 'off')&lt;/state>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;label>le nom de ce qui est activé ('tempogroup1', 'group1', 'off', 'on', 'presence', …)&lt;/label>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;value>la valeur de ce qui est activé (0, 100, 101, …, 105)&lt;/value>   
> &nbsp;&nbsp;&lt;/diagral>   
> &lt;/root>

À noter :   
  - `tempogroup` signifie que l'alarme va s'activer à la fin du temps sur le groupe indiqué.   
  - s'il y a plus d'un groupe d'activé en même temps, on considère que toute la maison est active, donc `label` va retourner "on" et `value` va retourner "100".   

