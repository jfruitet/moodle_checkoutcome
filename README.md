moodle_referentiel_m27
======================

referentiel plugin (mod / block / report) for Moodle 2.7 and further

By Jean FRUITET (jean.fruitet@univ-nantes.fr / jean.fruitet@free.fr)

2007/2014

Type: Activity Module
Requires: Moodle 2.7
Status: Contributed
Maintainer(s): jean.fruitet@univ-nantes.fr

== Documentation
    * French MoodleMoot2009 : http://moodlemoot2009.insa-lyon.fr/course/view.php?id=24
    * French MoodleMoot2010 : http://moodlemoot2010.utt.fr/course/view.php?id=33
    * French MoodleMoot2012 : http://moodlemoot2012.unimes.fr/course/view.php?id=33


== PRESENTATION (Français)

"referentiel" est un module Moodle destiné à implanter une activité de type certification de compétences.

Ce module permet :

- de spécifier un référentiel de compétences (ou de le télécharger) ;
- de déclarer des activités et d'associer celles-ci aux compétences du référentiel ;
- de gérer l'accompagnement ;
- de définir des tâches (mission, consignes, liste de compétences mobilisées pour accomplir la tâche, documents attachés) ;
- d'émettre des certificats basés sur le dit référentiel ;

- Si le site active les Objectifs, vous pouvez exporter le référentiel sous forme d'une
liste d'objectifs qui serviront alors à évaluer toute forme d'activité
(forum, BD, devoir, etc.)

Ces notations sont récupérées dans le module référentiel sous forme de compétences
validées dans des déclaration d'activité.

== PRESENTATION (English)

Skills repository ("referentiel") is a Moodle module for skill certification.
You can:
- specify a repository or import it
- declare activities linked with competencies
- follow students declarations
- propose tasks (a mission, list of competencies, linked documents...)
- export an print certificates

- If your site enables Outcomes (also known as Competencies, Goals, Standards or Criteria),
you can now export a list of Outcomes from referentiel module then grade things using
that scale (forum, database, assigments, etc.) throughout the site.
These grades will be integrated in Referentiel module.

== INSTALLATION (Français)

Ce plugin ets constitué de tros modules :

Le module activité Référentie qui doit être intégré dans le répertoire ./mod/ d'un serveur Moodle

Le bloc Référentiel qui doit être intégré dans le répertoire ./blocks/ du serveur

Le rapport Référentie qui doit être intégré dans le répertoire ./report/ du serveur

La procédure suivante s'applique à toute installation Moodle

VOTRE_DOSSIER_MOODLE = le nom du dossier où est placé votre moodle, en général "moodle"

URL_SERVEUR_MOODLE = le nom de votre serveur moodle, en général "http://machine.domaine.fr/moodle/"


1.

A) Décomprimer l'archive "referentiel-mod.zip" dans le dossier "VOTRE_DOSSIER_MOODLE/mod/"

Les fichiers de langue peuvent être laissés dans le dossier

"VOTRE_DOSSIER_MOODLE/mod/referentiel/lang/"

B) Décomprimer l'archive "referentiel-block.zip" dans le dossier "VOTRE_DOSSIER_MOODLE/blocks/"

Les fichiers de langue peuvent être laissés dans le dossier "VOTRE_DOSSIER_MOODLE/blocks/referentiel/lang/"

C) Décomprimer l'archive "referentiel-report.zip" dans le dossier "VOTRE_DOSSIER_MOODLE/report/"

Les fichiers de langue peuvent être laissés dans le dossier "VOTRE_DOSSIER_MOODLE/report/referentiel/lang/"

2. se loger avec le role admin sur "URL_SERVEUR_MOODLE"

3. Installer les différents éléments (ou les mises à jour) en passant par la rubrique

Administration / Notification

S'il y a des messages d'erreur m'avertir aussitôt par mail en m'envoyant une copie d'écran du message d'erreur.

4. paramétrer le module au niveau du site en passant par la rubrique

Administration du site / Plugins / Activités / Référentiel

Administration du site / Plugins / Bloc / Référentiel (rendre visible le bloc)

Administration du site / Plugins / Rapport / Référentiel



== INSTALLATION (English)

The following steps should get you up and running with this module code.

1. Unzip the archive referentiel-mod in moodle/mod/ directory

Unzip the archive referentiel-block in moodle/blocks/ directory

Unzip the archive referentiel-report in moodle/report/ directory

Languages files can be left in the ./referentiel/lang/ directories.

2. log on with admin role

3. install new module as usual (admin Notification)

4. Set module parameters

Administration / Plugins / Activity / Skills repository

Administration / Plugins / Blocks / Skills repository (make the block visible)

Administration / Plugins / Report / Skills repository


Referentiel Report functions

Functionnality "Skills repository report" (Referentiel report) for administrators
gives to administrators the opportunity to manage occurrences and instances of the referentiel module
and make archives of users numerical data

Unzip referentiel-report.zip
in 
YOUR_MOODLE/report/
 
