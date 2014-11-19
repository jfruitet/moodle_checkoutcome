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


== PRESENTATION (Fran�ais)

"referentiel" est un module Moodle destin� � implanter une activit� de type certification de comp�tences.

Ce module permet :

- de sp�cifier un r�f�rentiel de comp�tences (ou de le t�l�charger) ;
- de d�clarer des activit�s et d'associer celles-ci aux comp�tences du r�f�rentiel ;
- de g�rer l'accompagnement ;
- de d�finir des t�ches (mission, consignes, liste de comp�tences mobilis�es pour accomplir la t�che, documents attach�s) ;
- d'�mettre des certificats bas�s sur le dit r�f�rentiel ;

- Si le site active les Objectifs, vous pouvez exporter le r�f�rentiel sous forme d'une
liste d'objectifs qui serviront alors � �valuer toute forme d'activit�
(forum, BD, devoir, etc.)

Ces notations sont r�cup�r�es dans le module r�f�rentiel sous forme de comp�tences
valid�es dans des d�claration d'activit�.

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

== INSTALLATION (Fran�ais)

Ce plugin ets constitu� de tros modules :

Le module activit� R�f�rentie qui doit �tre int�gr� dans le r�pertoire ./mod/ d'un serveur Moodle

Le bloc R�f�rentiel qui doit �tre int�gr� dans le r�pertoire ./blocks/ du serveur

Le rapport R�f�rentie qui doit �tre int�gr� dans le r�pertoire ./report/ du serveur

La proc�dure suivante s'applique � toute installation Moodle

VOTRE_DOSSIER_MOODLE = le nom du dossier o� est plac� votre moodle, en g�n�ral "moodle"

URL_SERVEUR_MOODLE = le nom de votre serveur moodle, en g�n�ral "http://machine.domaine.fr/moodle/"


1.

A) D�comprimer l'archive "referentiel-mod.zip" dans le dossier "VOTRE_DOSSIER_MOODLE/mod/"

Les fichiers de langue peuvent �tre laiss�s dans le dossier

"VOTRE_DOSSIER_MOODLE/mod/referentiel/lang/"

B) D�comprimer l'archive "referentiel-block.zip" dans le dossier "VOTRE_DOSSIER_MOODLE/blocks/"

Les fichiers de langue peuvent �tre laiss�s dans le dossier "VOTRE_DOSSIER_MOODLE/blocks/referentiel/lang/"

C) D�comprimer l'archive "referentiel-report.zip" dans le dossier "VOTRE_DOSSIER_MOODLE/report/"

Les fichiers de langue peuvent �tre laiss�s dans le dossier "VOTRE_DOSSIER_MOODLE/report/referentiel/lang/"

2. se loger avec le role admin sur "URL_SERVEUR_MOODLE"

3. Installer les diff�rents �l�ments (ou les mises � jour) en passant par la rubrique

Administration / Notification

S'il y a des messages d'erreur m'avertir aussit�t par mail en m'envoyant une copie d'�cran du message d'erreur.

4. param�trer le module au niveau du site en passant par la rubrique

Administration du site / Plugins / Activit�s / R�f�rentiel

Administration du site / Plugins / Bloc / R�f�rentiel (rendre visible le bloc)

Administration du site / Plugins / Rapport / R�f�rentiel



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
 
