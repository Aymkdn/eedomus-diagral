# Pr�-Requis

Il vous faut une alarme Diagral connect�e et utilisable avec l'application e-ONE.

# Installation

Installer le plugin depuis le store Eedomus.

## Configuration

Lors de l'installation, un nouvel appareil `Diagral Alarme` est cr��, et on vous demande de remplir plusieurs param�tres :

![Champs de configuration](https://user-images.githubusercontent.com/946315/167384621-0ce5b79f-14e8-49ab-a23f-cd7306e97781.png)

`username` et `password` sont ceux utilis�s pour vous connecter � l'application e-ONE. Si vous avez un `&` dans votre password, alors remplacez le par `%26`.

`mastercode` est votre code de s�curit� � 4 chiffres que vous utilisez pour g�rer la centrale.

`systemname` est le nom du syst�me tel que d�fini dans l'application e-ONE quand vous allez dans `Param�trage`, puis dans `G�rer mon profil et mes acc�s`, puis `Changer le nom du syst�me`.

# Utilisation

Une fois l'appareil cr��, vous pouvez aller voir dans l'onglet **Valeurs** :

![Liste des valeurs](https://user-images.githubusercontent.com/946315/173418136-e210bdb8-3a2e-4571-88ca-795224d41a0d.png)

Plusieurs valeurs sont possibles :   
  - `0` pour `Off` (�teindre l'alarme) qui correspond � la d�sactivation totale de l'alarme ;    
  - `100` pour `On` (allumer l'alarme) qui correspond � l'activation totale de l'alarme ;    
  - de `101` � `104` (allumer un groupe de 1 � 4) qui correspond � l'activation d'un groupe ;    
  - `105` (allumer la pr�sence) qui correspond � l'activation du mode pr�sence.

L'�dition de la celulle "Param�tres" va donner quelque chose comme �a :
> &username=abcdef@something.com&password=votremotdepasse&mastercode=1234&systemname=Maison&action=[RAW_VALUE]

## Utilisation avec Alexa

Pour activer/d�sactive l'alarme � la voix, vous pouvez aller dans `Configurer` de votre box eedomus, puis dans la section de votre assistant vocal, activez les deux cases pour l'appareil : 

![�teint et allume sont coch�s sous l'appareil Alarme](https://user-images.githubusercontent.com/946315/167385335-cff26b42-5366-46e6-b0bd-6a21ac7d49c6.png)

Donnez le m�me nom aux deux. Ici j'ai mis "Alarme".

J'ai test� avec Alexa, et cette op�ration m'a permis d'avoir un objet "Alarme" que je peux d�clencher � la voix avec _� allume l'alarme �_.

## Utilisation avec Google

L'astuce d�crite ci-dessus pour Alexa ne semble pas fonctionner correctement avec Google. Pour celui-ci, il faudra plut�t se tourner vers [IFTTT](https://ifttt.com) et faire un appel � l'API de la box eedomus.

## Retour d'�tat

Le retour d'�tat se fait directement sur l'appareil.

On peut aussi appeler l'URL [http://localhost/script/?exec=diagral.php&username=USERNAME&password=PASSWORD&mastercode=MASTERCODE&systemname=SYSTEMNAME&action=state](http://localhost/script/?exec=diagral.php&username=USERNAME&password=PASSWORD&mastercode=MASTERCODE&systemname=SYSTEMNAME&action=state) pour avoir le fichier XML ci-dessous :    
> &lt;root>   
> &nbsp;&nbsp;&lt;diagral>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;error>message d'erreur (s'il existe)&lt;/error>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;state>le statut de l'alarme (soit 'on', soit 'off')&lt;/state>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;label>le nom de ce qui est activ� ('tempogroup1', 'group1', 'off', 'on', 'presence', �)&lt;/label>   
> &nbsp;&nbsp;&nbsp;&nbsp;&lt;value>la valeur de ce qui est activ� (0, 100, 101, �, 105)&lt;/value>   
> &nbsp;&nbsp;&lt;/diagral>   
> &lt;/root>

� noter :   
  - `tempogroup` signifie que l'alarme va s'activer � la fin du temps sur le groupe indiqu�.   
  - s'il y a plus d'un groupe d'activ� en m�me temps, on consid�re que toute la maison est active, donc `label` va retourner "on" et `value` va retourner "100".   

