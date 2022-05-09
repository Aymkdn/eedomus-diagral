# Pré-Requis

Il vous faut une alarme Diagral connectée et utilisable avec l'application e-ONE.

# Installation

Installer le plugin depuis le store Eedomus.

## Configuration

Lors de l'installation, un nouvel appareil `Diagral Alarme` est créé, et on vous demande de remplir plusieurs paramètres :

![Champs de configuration](https://user-images.githubusercontent.com/946315/167384621-0ce5b79f-14e8-49ab-a23f-cd7306e97781.png)

`username` et `password` sont ceux utilisés pour vous connecter à l'application e-ONE.

`mastercode` est votre code de sécurité à 4 chiffres que vous utilisez pour gérer la centrale.

`systemname` est le nom du système tel que défini dans l'application e-ONE quand vous allez dans `Paramétrage`, puis dans `Gérer mon profil et mes accès`, puis `Changer le nom du système`.

# Utilisation

Une fois l'appareil créé, vous pouvez aller voir dans l'onglet **Valeurs** :

![Liste des valeurs](https://user-images.githubusercontent.com/946315/167385123-4f28f4cd-3d0d-46fd-a8e7-79f2761c4ffa.png)

Deux valeurs sont possibles : `0` pour `Off` (éteindre l'alarme), et `100` pour `On` (allumer l'alarme). Cela correspond à l'action/désactivation totale de l'alarme.

L'édition de la celulle "Paramètres" va donner quelque chose comme ça :
> _&username=abcdef@something.com&password=votremotdepasse&mastercode=1234&systemname=Maison&action=[RAW_VALUE]_

## Utilisation avec Google et Alexa

Pour activer/désactive l'alarme à la voix, vous pouvez aller dans `Configurer` de votre box eedomus, puis dans la section de votre assistant vocal, activez les deux cases pour l'appareil : 

![éteint et allume sont cochés sous l'appareil Alarme](https://user-images.githubusercontent.com/946315/167385335-cff26b42-5366-46e6-b0bd-6a21ac7d49c6.png)

Donnez le même nom aux deux. Ici j'ai mis "Alarme".

J'ai seulement testé avec Alexa, et cette opération m'a permis d'avoir un objet "Alarme" que je peux déclencher à la voix avec _« allume l'alarme »_.
