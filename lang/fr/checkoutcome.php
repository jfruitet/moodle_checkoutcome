<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * French strings for checkoutcome
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['language'] = 'Fr';
$string['modulename'] = 'Suivi d\'objectifs';
$string['modulenameplural'] = 'Suivi d\'objectifs';
$string['modulename_help'] = 'Utilisez le module Suivi d\'objectifs pour... | Le module Suivi d\'objectifs permet...';
$string['checkoutcomefieldset'] = 'Custom example fieldset';
$string['checkoutcomename'] = 'Suivi d\'objectifs';
$string['checkoutcomename_help'] = 'Le module Suivi d\'objectifs permet à un élève de s\'auto-évaluer sur les objectifs du cours et à l\'enseignant de valider cette évaluation.';
$string['checkoutcome'] = 'Suivi d\'objectifs';
$string['pluginadministration'] = 'Administration du module Suivi d\'objectifs';
$string['pluginname'] = 'checkoutcome';
$string['empty_list'] = 'La liste des objectifs est vide.';
$string['empty_student_list'] = 'La liste des étudiants est vide.';
$string['OK'] = 'OK';
$string['back'] = 'Retour';
$string['backtolist'] = 'Retour à la liste';
$string['no_category_name'] = 'Pas de groupe d\'objectifs défini';
$string['no_category_desc'] = 'Aucun groupe n\'a été défini pour ces objectifs';
$string['validate'] = 'Envoyer';
$string['select_item'] = 'Choisissez dans la liste';
$string['teacher_grading'] = 'Evaluation enseignant';
$string['student_grading'] = 'Déclaration étudiant';
$string['save_grades'] = 'Enregistrer les notes';
$string['lastdatestudent'] = 'Dernière modification par l\'étudiant :';
$string['lastdateteacher'] = 'Dernière modification par l\'enseignant :';
$string['page_refresh'] = 'Rafraichir la page';
$string['export_pdf'] = 'Obtenir un fichier pdf';
$string['bynameondate'] = 'par {$a->name} - {$a->date}';
$string['validatedbynameondate'] = 'évalué par {$a->name} - {$a->date}';
$string['graded_item'] = 'Evalué : ';
$string['maxlength'] = '(1000 caractères max.)';
$string['finalgrade'] = 'Evaluation : ';
$string['view_details'] = 'En savoir plus sur cet objectif';
$string['add_student_description'] = 'Ajouter une description de la période';
$string['edit_student_description'] = 'Editer la description de la période';
$string['student_description'] = 'Description de la période';

// Tabs
$string['view'] = 'Auto-évaluation';
$string['preview'] = 'Aperçu';
$string['report'] = 'Bilan';
$string['edit'] = 'Objectifs';
$string['list_cat'] = 'Groupes d\'objectifs';
$string['list_disp'] = 'Affichages';
$string['list_gradings'] = 'Evaluations';
$string['setting'] = 'Paramétrage';
$string['summary'] = 'Résumé';
$string['export'] = 'Export';
$string['list_period'] = 'Périodes';

// Capabilities
$string['checkoutcome:addinstance'] = 'Créer un nouveau module';
$string['checkoutcome:edit'] = 'Editer le module';
$string['checkoutcome:updateown'] = 'Modifier ses propres données';
$string['checkoutcome:updateother'] = 'Modifier les données des autres';
$string['checkoutcome:preview'] = 'Prévisualiser';
$string['checkoutcome:viewreports'] = 'Consulter les rapports';
$string['checkoutcome:emailoncomplete'] = 'Recevoir un email lorsque l\'étudiant a complété la liste';
$string['checkoutcome:updatelocked'] = 'Modifier des données verrouillées';
$string['checkoutcome:viewmenteereports'] = 'View mentee reports';
$string['checkoutcome:viewallcoursegroups'] = 'Voir tous les groupes du cours';

//Errors
$string['error_cmid'] = 'L\'ID du module de cours est incorrect';
$string['error_specif_id'] = 'Vous devez spécifier l\'ID d\'un module de cours ou d\'une instance';
$string['error_update'] = 'Error : you do not have permission to update this list';
$string['error_sesskey'] = 'Error : invalid session key';
$string['error_itemid'] = 'Error: invalid (or missing) items list';
$string['request_too_long'] = 'Une raison possible : votre sélection est trop large, essayez de la réduire';
$string['noteacherfound'] = 'Pas d\'enseignant trouvé';
$string['nostudentfound'] = 'Pas d\'étudiant trouvé';
$string['no_item_found'] = 'Pas d\'objectif trouvé';
$string['database_update_failed'] = 'La mise à jour de la base de données a échoué';

//Group
$string['add_category'] = 'Ajouter un nouveau groupe d\'objectifs';
$string['edit_category'] = 'Editer le groupe d\'objectifs';
$string['category_name'] = 'Nom';
$string['category_description'] = 'Description';
$string['editcategory'] = 'Editer ce groupe d\'objectifs';
$string['delete_category'] = 'Supprimer ce groupe d\'objectifs';
$string['delete_category_confirm'] = 'Voulez-vous vraiment supprimer ce groupe?';
$string['input_name_category'] = 'groupe d\'objectifs';
$string['category'] = 'Groupe d\'objectifs';
$string['category_help'] = 'Dans ce module, les objectifs peuvent être classés par groupes. Le champ "Nom abrégé" sert à ordonner les périodes';
$string['empty_category_list'] = 'La liste des groupes est vide.';

//Edit
$string['shortname'] = 'Nom abrégé';
$string['fullname'] = 'Nom complet';
$string['teacher_scale'] = 'Barême';
$string['student_scale'] = 'Barême étudiant';
$string['type'] = 'Type';
$string['display_choice'] = 'Affichage';
$string['category_choice'] = 'Groupe d\'objectifs';
$string['sujet_normal'] = 'examen d\'un sujet normal';
$string['semio_desc'] = 'sémiologie descriptive';
$string['semio_syndro'] = 'sémiologie syndromique';
$string['raison_diag'] = 'raisonnement diagnostique';
$string['gestes_tech'] = 'gestes techniques';
$string['NA'] = 'Non défini';
$string['in_use'] = 'Utilisé';
$string['edit_item'] = 'Editer';
$string['delete'] = 'Supprimer';
$string['update_outcomes'] = 'Modifier les objectifs sélectionnés';
$string['update_outcomes_desc'] = 'Modifier les objectifs sélectionnés avec les valeurs de groupes d\'objectifs et d\'affichage sélectionnées';
$string['delete_outcomes'] = 'Supprimer les objectifs sélectionnés';
$string['delete_outcomes_desc'] = 'Supprimer les objectifs sélectionnés';
$string['link'] = 'Lien';
$string['edit_link'] = 'Editer le lien';
$string['add_link'] = 'Ajouter un lien';
$string['delete_link'] = 'Supprimer le lien';
$string['new_link_outcome'] = 'Ajouter un lien pour l\'objectif ';
$string['delete_link_outcome'] = 'Supprimer le lien pour l\'objectif ';
$string['delete_link_question'] = 'Voulez-vous vraiment supprimer ce lien ?';
$string['counter'] = 'Compteur';
$string['countergoal'] = 'Objectif compteur';

//Edit outcome
$string['outcome'] = 'Objectif';
$string['outcome_help'] = 'Cet objectif peut être évalué par l\'enseignant et par l\'étudiant lui-même. Pour l\'évaluation, un barême sera proposé à l\'enseignant, un barême différent peut être proposé à l\'étudiant.';
$string['outcome_name'] = 'Nom';
$string['edit_outcome'] = 'Editer l\'objectif';
$string['delete_outcome_confirm'] = 'Voulez-vous vraiment supprimer cet objectif ?';
$string['add_outcome'] = 'Ajouter de nouveaux objectifs';
$string['add_outcome_desc'] = 'Associer de nouveaux objectifs au module en les sélectionnant dans la liste des objectifs du cours';
$string['cancel'] = 'Annuler';
$string['deleteall_outcome'] = 'Supprimer tous les objectifs non utilisés';
$string['deleteall_outcome_confirm'] = 'Voulez-vous vraiment supprimer tous les objectifs ?\nSeuls les objectifs qui ne sont pas utilisés seront supprimés.';
$string['delete_outcome_confirm'] = 'Voulez-vous vraiment supprimer les objectifs sélectionnés?\nSeuls les objectifs qui ne sont pas utilisés seront effectivement supprimés.';

//Add outcome
$string['select_default_display'] = 'Affichage par défaut : ';
$string['select_default_category'] = 'Groupe par défaut : ';
$string['select_default_teacherscale'] = 'Barème enseignant par défaut : ';
$string['select_default_studentscale'] = 'Barème étudiant par défaut : ';
$string['enable_defaults'] = 'Définir des valeurs par défaut (affichage, groupe)';
$string['check_uncheck_all'] = 'Tout Cocher/Décocher';
$string['add_comment'] = 'Ajouter un commentaire';

// Comment and document
$string['add_comment'] = 'Ajouter un commentaire';
$string['add_document'] = 'Ajouter un lien vers un document';
$string['edit_comment'] = 'Editer le commentaire';
$string['delete_comment'] = 'Supprimer le commentaire';
$string['delete_comment_confirm'] = 'Voulez-vous vraiment supprimer ce commentaire ?';
$string['input_name_document'] = 'document';
$string['document_help'] = 'Ce formulaire va vous aider à créer un lien vers un document';
$string['document_title'] = 'Titre';
$string['document_description'] = 'Description';
$string['document'] = 'document';
$string['edit_document'] = 'Editer le document';
$string['delete_document'] = 'Supprimer le document';
$string['delete_document_confirm'] = 'Voulez-vous vraiment supprimer ce document ?';
$string['document_url_old'] = 'Fichier courant';

// Display
$string['display'] = 'Affichage';
$string['display_name'] = 'Nom';
$string['display_description'] = 'Description';
$string['edit_display'] = 'Editer l\'affichage';
$string['delete_display'] = 'Supprimer l\'affichage';
$string['delete_display_confirm'] = 'Voulez-vous vraiment supprimer cet affichage ?';
$string['add_display'] = 'Ajouter un affichage';
$string['input_name_display'] = 'affichage';
$string['display_help'] = 'Les objectifs peuvent être affichés par une couleur de fond différente';
$string['color_choice'] = 'Couleur de fond';
$string['color_code'] = 'Code couleur d\'arrière-plan';
$string['is_white_font'] = 'Couleur de police blanche';
$string['empty_display_list'] = 'La liste des affichages est vide.';

// Period
$string['period'] = 'Période';
$string['lock'] = 'Période vérouillée';
$string['markperiod'] = 'Période de référence';
$string['yes'] = 'Oui';
$string['no'] = 'Non';
$string['update_periods'] = 'Modifier les périodes sélectionnés';
$string['update_periods_desc'] = 'Modifier les périodes sélectionnés avec les valeurs de période vérouillée et de période de référence';
$string['period_name'] = 'Nom';
$string['period_description'] = 'Description';
$string['edit_period'] = 'Editer la période';
$string['delete_period'] = 'Supprimer la période';
$string['delete_period_confirm'] = 'Voulez-vous vraiment supprimer cette période ?';
$string['add_period'] = 'Ajouter une période';
$string['input_name_period'] = 'période';
$string['period_help'] = 'Le suivi des objectifs peut être géré sur plusieurs périodes. On reprend les mêmes objectifs pour chaque période, les évaluations de chaque période sont indépendantes. Le champ "Nom abrégé" sert à ordonner les périodes';
$string['empty_period_list'] = 'La liste des périodes est vide.';
$string['dateyesno'] = 'Afficher les dates';
$string['start_date'] = 'Date de début';
$string['end_date'] = 'Date de fin';
$string['startdate_inf_enddate'] = 'La date de fin de la période doit être postérieure à la date de début de la période';
$string['default_period_name'] = 'Période par défaut';
$string['period_goals'] = 'Consignes de la Période';
$string['period_goals_view'] = 'Voir';
$string['period_goals_edit'] = 'Editer';
$string['nostudent'] = 'Pas d\'étudiant défini.';
$string['goal'] = 'Consignes';
$string['period_appraisals'] = 'Appréciations de la Période';
$string['appraisal'] = 'Appréciation';
$string['emptystudentorperiod'] = 'l\'id de l\'étudiant ou de la période est manquant';
$string['nogoal'] = 'Pas de consigne';
$string['noappraisal'] = 'Pas d\'appréciation';
$string['backtoview'] = 'Retour au suivi';

// Grading
$string['name'] = 'Prénom / Nom';
$string['email'] = 'Adresse de messagerie';
$string['student_rate'] = 'Taux de réponse de l\'étudiant (%)';
$string['student_last'] = 'Dernière modification';
$string['teacher_rate'] = 'Taux de réponse du professeur (%)';
$string['teacher_last'] = 'Dernière modification';
$string['feedback'] = 'Commentaire';
$string['status'] = 'Status';
$string['action_grade'] = 'Evaluer';
$string['action_update'] = 'Modifier';
$string['by'] = 'par';

// Report
$string['input'] = 'Entrée';
$string['output'] = 'Sortie';
$string['criteria_category'] = 'Groupe d\'objectifs : ';
$string['all_categories'] = 'Tous les groupes';
$string['criteria_display'] = 'Affichage : ';
$string['all_displays'] = 'Tous les affichages';
$string['results_per_outcome'] = 'Résultats par objectif : ';
$string['no_results'] = 'Il n\'y aucun résultat correspondant aux critères fournis';
$string['mean_results'] = 'Moyenne des résultats avec les critères suivants :';
$string['mean_calculation_impossible'] = 'Calcul de la moyenne impossible, les objectifs n\'ont pas tous le même barême.';
$string['for_category'] = 'groupe';
$string['for_display'] = 'affichage';
$string['filter_scale'] = 'Barême : ';
$string['filter_category'] = 'Groupe d\'objectifs: ';
$string['filter_display'] = 'Affichage : ';
$string['filter_outcome'] = 'Objectif : ';
$string['teacher_student'] = 'Enseignant et Etudiant';
$string['teacher_only'] = 'Enseignant seul';
$string['student_only'] = 'Etudiant seul';
$string['teacher'] = 'Enseignant';
$string['student'] = 'Etudiant';
$string['all'] = 'Tous';
$string['calculate'] = 'Calculer';
$string['student_selection'] = 'Barême étudiant';
$string['teacher_selection'] = 'Barême enseignant';
$string['teacherrate'] = 'Enseignant (%)';
$string['studentrate'] = 'Etudiant (%)';
$string['counter'] = 'Compteur';
$string['not_graded'] = 'Pas de réponse';
$string['answer'] = 'Réponse';
$string['no_category'] = 'Aucun groupe n\'a été trouvé';
$string['no_display'] = 'Aucun affichage n\'a été trouvé';
$string['calculation_impossible_for_categories'] = 'Le Calcul demandé est impossible pour les groupes suivants qui contiennent des objectifs liés à des barêmes différents :';
$string['calculation_impossible_for_displays'] = 'Le Calcul demandé est impossible pour les affichages suivants qui sont appliqués sur des objectifs liés à des barêmes différents :';
$string['value'] = 'valeur';
$string['scale'] = 'barême';
$string['gradeexporttype'] = 'Type d\'export';
$string['gradeexportsource'] = 'Notes à exporter';
$string['no_scale_found'] = 'Pas de barême trouvé';
$string['criteria_period'] = 'Période : ';
$string['all_periods'] = 'Toutes les périodes';
$string['criteria_student'] = 'Etudiant : ';
$string['all_students'] = 'Tous les étudiants';
$string['criteria_valuetype'] = 'Type de valeur : ';
$string['valuetype_percent'] = 'Pourcentage';
$string['valuetype_color'] = 'Couleur';
$string['criteria_student_group'] = 'Groupe d\'étudiant : ';
$string['all_student_groups'] = 'Tous les groupes';
$string['sepsemicolon'] = 'Point-virgule';
$string['exportcounter'] = 'Inclure les compteurs dans l\'exportation';

// export PDF
$string['student_comment'] = 'Commentaire de l\'étudiant : ';
$string['teacher_feedback'] = 'Commentaire de l\'enseignant : ';
$string['attached_documents'] = 'Documents : ';
$string['date_pdf'] = 'Date de l\'export : ';
$string['exporter'] = 'Exporté par : ';
$string['author'] = 'Auteur : ';
$string['exportpdftoportfolio'] = 'Exporter un fichier PDF vers le portfolio';
$string['exportcategorytoportfolio'] = 'Exporter les objectifs validés du groupe d\'objectifs vers le portfolio';
$string['invalidcategoryid'] = 'Category ID invalide';
$string['mustprovidecategoryorpdffile'] = 'Vous devez préciser un groupe d\'objectifs ou un fichier pdf';
$string['errorstoringpdffile'] = 'Une erreur s\'est produite lors du stockage du fichier pdf' ;
$string['mustprovideexpectedarguments'] = 'Vous devez fournir les paramètres attendus';
$string['savegrades'] = 'Cliquer sur OK pour sauvegarder les notes modifiées ou Annuler pour quitter sans sauvegarder';
$string['emptyitemlist'] = 'Désolé, la liste des objectifs validés est vide, l\'export est donc impossible';
$string['periodnotfound'] = 'Désolé, la période sélectionnée n\'a pas été trouvée';

// Events
$string['eventcheckoutcomecomplete'] = 'Cette liste d\'objectifs est complète';
$string['eventeditpageviewed'] = 'Editer la page affichée';
$string['eventreportviewed'] = 'Rapport affiché';
$string['eventstudentchecksupdated'] = 'Liste d\'objectifs de l\'étudiant mise à jour';
$string['eventstudentdescriptionupdated'] = 'Description d\'un item de la liste d\'objectifs mise à jour';
$string['eventteacherchecksupdated'] = 'Evaluation d\'objectif par l\'enseignant mise à jour';