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
 * Italian language strings for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Teams Meeting';
$string['modulenameplural'] = 'Teams Meetings';
$string['modulename_help'] = 'Il modulo Teams Meeting permette ai docenti di creare e gestire meeting Microsoft Teams direttamente dai corsi Moodle, con tracciamento automatico delle presenze.';
$string['pluginname'] = 'Teams Meeting';
$string['pluginadministration'] = 'Amministrazione Teams Meeting';

// Impostazioni
$string['teamsmeetings:addinstance'] = 'Aggiungere un nuovo Teams Meeting';
$string['teamsmeetings:view'] = 'Visualizzare Teams Meeting';
$string['teamsmeetings:viewattendance'] = 'Visualizzare report presenze';
$string['teamsmeetings:exportattendance'] = 'Esportare report presenze';
$string['teamsmeetings:managemeeting'] = 'Gestire Teams Meeting';
$string['teamsmeetings:syncattendance'] = 'Sincronizzare presenze da Teams';
$string['teamsmeetings:deleteattendance'] = 'Eliminare registrazioni presenze';

// Form campi
$string['meetingname'] = 'Nome del meeting';
$string['meetingname_help'] = 'Nome che verrà visualizzato nel corso e in Microsoft Teams';
$string['meetingsettings'] = 'Impostazioni meeting';
$string['starttime'] = 'Ora di inizio';
$string['starttime_help'] = 'Data e ora di inizio del meeting';
$string['endtime'] = 'Ora di fine';
$string['endtime_help'] = 'Data e ora di fine del meeting (opzionale)';
$string['recurring'] = 'Meeting ricorrente';
$string['recurring_help'] = 'Attiva se il meeting si ripete regolarmente';
$string['recurrencepattern'] = 'Schema di ricorrenza';
$string['recurrenceend'] = 'Fine ricorrenza';
$string['daily'] = 'Giornaliero';
$string['weekly'] = 'Settimanale';
$string['monthly'] = 'Mensile';

// Teams opzioni
$string['teamsoptions'] = 'Opzioni Microsoft Teams';
$string['allowanonymous'] = 'Permetti accesso anonimo';
$string['allowanonymous_help'] = 'Permette agli utenti di partecipare senza autenticazione';
$string['autoadmitusers'] = 'Ammissione automatica utenti';
$string['everyoneincompany'] = 'Tutti nell\'organizzazione';
$string['everyone'] = 'Tutti';
$string['everyoneincompanyexcludingguests'] = 'Tutti nell\'organizzazione esclusi gli ospiti';
$string['organizeronly'] = 'Solo l\'organizzatore';
$string['recordmeeting'] = 'Registra meeting';
$string['recordmeeting_help'] = 'Registra automaticamente il meeting quando inizia';

// Tracciamento presenze
$string['attendancetracking'] = 'Tracciamento presenze';
$string['trackattendance'] = 'Traccia presenze';
$string['trackattendance_help'] = 'Attiva il tracciamento automatico delle presenze';
$string['minduration'] = 'Durata minima (minuti)';
$string['minduration_help'] = 'Tempo minimo di presenza per essere considerati presenti';

// Notifiche
$string['notifications'] = 'Notifiche';
$string['sendreminder'] = 'Invia promemoria';
$string['remindertime'] = 'Tempo promemoria';
$string['minutes15'] = '15 minuti prima';
$string['minutes30'] = '30 minuti prima';
$string['hour1'] = '1 ora prima';
$string['day1'] = '1 giorno prima';

// Errori validazione
$string['endtimebeforestarttime'] = 'L\'ora di fine deve essere successiva all\'ora di inizio';
$string['invalidminduration'] = 'La durata minima deve essere almeno 1 minuto';

// Pagina visualizzazione
$string['meetingdetails'] = 'Dettagli meeting';
$string['status'] = 'Stato';
$string['scheduled'] = 'Programmato';
$string['inprogress'] = 'In corso';
$string['ended'] = 'Terminato';
$string['joinmeeting'] = 'Partecipa al meeting';
$string['meetingnotstartedyet'] = 'Il meeting non è ancora iniziato';
$string['meetinghasended'] = 'Il meeting è terminato';
$string['nojoinurl'] = 'URL di partecipazione non disponibile';

// Report presenze
$string['attendancereport'] = 'Report presenze';
$string['viewattendancereport'] = 'Visualizza report presenze';
$string['participant'] = 'Partecipante';
$string['firstjoin'] = 'Primo accesso';
$string['lastleave'] = 'Ultima uscita';
$string['totalduration'] = 'Durata totale';
$string['sessions'] = 'Sessioni';
$string['noattendancedata'] = 'Nessun dato di presenza disponibile';
$string['attendancedetailsfor'] = 'Dettagli presenza per {$a}';
$string['session'] = 'Sessione';
$string['jointime'] = 'Ora di ingresso';
$string['leavetime'] = 'Ora di uscita';
$string['duration'] = 'Durata';
$string['ipaddress'] = 'Indirizzo IP';
$string['stillconnected'] = 'Ancora connesso';
$string['total'] = 'Totale';
$string['nosessions'] = 'Nessuna sessione trovata';
$string['attendancepercent'] = 'Presenza %';
$string['viewdetails'] = 'Visualizza dettagli';
$string['meetingduration'] = 'Durata meeting';
$string['syncattendance'] = 'Sincronizza presenze';
$string['lastsync'] = 'Ultima sincronizzazione';

// Export
$string['exportoptions'] = 'Opzioni di esportazione';
$string['downloadcsv'] = 'Scarica CSV';
$string['downloadexcel'] = 'Scarica Excel';
$string['downloadpdf'] = 'Scarica PDF';

// Impostazioni plugin
$string['settings'] = 'Impostazioni Teams Meeting';
$string['tenant_id'] = 'Tenant ID';
$string['tenant_id_desc'] = 'Microsoft Azure Tenant ID della tua organizzazione';
$string['client_id'] = 'Client ID';
$string['client_id_desc'] = 'Application (client) ID dall\'app registrata in Azure';
$string['client_secret'] = 'Client Secret';
$string['client_secret_desc'] = 'Client secret dall\'app registrata in Azure';

// Eventi
$string['eventmeetingcreated'] = 'Meeting Teams creato';
$string['eventmeetingjoined'] = 'Partecipazione a meeting Teams';
$string['eventattendanceviewed'] = 'Report presenze visualizzato';
$string['eventattendancelogged'] = 'Presenza registrata';
$string['eventattendancesynced'] = 'Presenze sincronizzate';
$string['eventcoursemoduleviewed'] = 'Modulo Teams Meeting visualizzato';

// Backup
$string['attendancerecords'] = 'Registrazioni presenze';

// Errori
$string['failedtogetaccesstoken'] = 'Impossibile ottenere il token di accesso da Microsoft';
$string['missingparameter'] = 'Parametro mancante';
$string['nomeetingid'] = 'ID meeting Teams non trovato';
$string['failedtosyncattendance'] = 'Impossibile sincronizzare i dati delle presenze';
$string['syncpartialcomplete'] = 'Sincronizzazione parziale: {$a->synced} utenti sincronizzati, {$a->errors} errori';
$string['synccomplete'] = 'Sincronizzazione completata: {$a} utenti sincronizzati';
$string['syncerror'] = 'Errore durante la sincronizzazione delle presenze';
$string['meetingcreationerror'] = 'Errore nella creazione del meeting: {$a}';
$string['meetingupdateerror'] = 'Errore nell\'aggiornamento del meeting: {$a}';
$string['meetingdeleteerror'] = 'Errore nell\'eliminazione del meeting: {$a}';
$string['syncerrorwithmsg'] = 'Errore durante la sincronizzazione delle presenze: {$a}';

// Impostazioni avanzate
$string['configintro'] = 'Configura l\'integrazione con Microsoft Teams per permettere la creazione di meeting e il tracciamento delle presenze.';
$string['azuresettings'] = 'Impostazioni Azure Active Directory';
$string['azuresettings_desc'] = 'Configura le credenziali dell\'applicazione registrata in Azure AD per accedere alle API di Microsoft Graph.';
$string['defaultmeetingsettings'] = 'Impostazioni predefinite meeting';
$string['defaultmeetingsettings_desc'] = 'Valori predefiniti per i nuovi meeting Teams.';
$string['defaultautoadmit'] = 'Ammissione automatica predefinita';
$string['defaultautoadmit_desc'] = 'Chi può bypassare la sala d\'attesa per impostazione predefinita';
$string['defaultrecording'] = 'Registrazione predefinita';
$string['defaultrecording_desc'] = 'Attiva la registrazione automatica per impostazione predefinita';
$string['defaultattendance'] = 'Tracciamento presenze predefinito';
$string['defaultattendance_desc'] = 'Attiva il tracciamento presenze per impostazione predefinita';
$string['defaultminduration'] = 'Durata minima predefinita';
$string['defaultminduration_desc'] = 'Minuti minimi di presenza per essere considerati presenti (predefinito)';
$string['cronsettings'] = 'Impostazioni sincronizzazione';
$string['cronsettings_desc'] = 'Configura la frequenza di sincronizzazione dei dati di presenza da Teams.';
$string['syncfrequency'] = 'Frequenza sincronizzazione';
$string['syncfrequency_desc'] = 'Quanto spesso sincronizzare i dati di presenza da Microsoft Teams';
$string['minutes5'] = '5 minuti';
$string['minutes10'] = '10 minuti';
$string['advancedsettings'] = 'Impostazioni avanzate';
$string['advancedsettings_desc'] = 'Opzioni avanzate per debug e ottimizzazione.';
$string['debugmode'] = 'Modalità debug';
$string['debugmode_desc'] = 'Attiva log dettagliati per troubleshooting';
$string['cacheduration'] = 'Durata cache (secondi)';
$string['cacheduration_desc'] = 'Quanto tempo mantenere in cache i token di accesso';

// Avviso endpoint Beta
$string['betaendpointwarning'] = 'La rilevazione presenze si basa sulle API Beta di Microsoft Graph che potrebbero cambiare o smettere di funzionare.';

// Task schedulati
$string['syncattendancetask'] = 'Sincronizzazione presenze Teams Meeting';

// Messaggi generali
$string['nomeetings'] = 'Nessun Teams Meeting in questo corso';

// Utenti non mappati
$string['unmatchedusers'] = 'Utenti Teams non riconosciuti';
$string['backtoattendance'] = 'Torna al report presenze';
$string['nounmatchedusers'] = 'Tutti gli utenti Teams sono stati riconosciuti correttamente.';
$string['unmatchedusers_help'] = 'Questi utenti hanno partecipato al meeting Teams ma non sono stati riconosciuti automaticamente. Puoi associarli manualmente agli utenti del corso.';
$string['teamsemail'] = 'Email Teams';
$string['teamsdisplayname'] = 'Nome visualizzato Teams';
$string['maptouser'] = 'Associa a utente Moodle';
$string['selectuser'] = 'Seleziona utente...';
$string['map'] = 'Associa';
$string['usermapped'] = 'Utente associato con successo e presenze aggiornate.';