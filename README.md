# Plugin Teams Meeting per Moodle

Plugin per Moodle che permette ai docenti di creare e gestire meeting Microsoft Teams direttamente dai corsi, con tracciamento automatico delle presenze.

## Caratteristiche principali

- ✅ Creazione meeting Teams direttamente da Moodle
- ✅ Se la creazione diretta fallisce, il plugin prova automaticamente via calendario (richiede permesso Calendars.ReadWrite)
- ✅ Integrazione completa con Microsoft Teams
- ✅ Tracciamento automatico delle presenze
- ✅ Report dettagliati con ora di connessione/disconnessione
- ✅ Export presenze in CSV/Excel/PDF
- ✅ Meeting ricorrenti
- ✅ Notifiche e promemoria automatici
- ✅ Registrazione automatica meeting (opzionale)

## Requisiti

- Moodle 4.1 o superiore
- PHP 7.4 o superiore
- Account Microsoft 365 con licenza Teams
- App registrata in Azure Active Directory

## Installazione

1. **Download del plugin**
   ```bash
   cd /path/to/moodle/mod
   git clone https://github.com/yourusername/moodle-mod_teamsmeetings.git teamsmeetings
   ```

2. **Installazione via Moodle**
   - Accedi come amministratore
   - Vai su "Amministrazione del sito" → "Notifiche"
   - Segui il processo di installazione

## Configurazione Azure AD

### 1. Registrare l'applicazione in Azure

1. Accedi al [portale Azure](https://portal.azure.com)
2. Vai su "Azure Active Directory" → "Registrazioni app"
3. Clicca "Nuova registrazione"
4. Configura:
   - Nome: `Moodle Teams Integration`
   - Tipi di account supportati: "Account in questa directory organizzativa"
   - URI di reindirizzamento: lascia vuoto per ora

### 2. Configurare i permessi API

Nella tua app registrata:

1. Vai su "Autorizzazioni API"
2. Clicca "Aggiungi un'autorizzazione"
3. Seleziona "Microsoft Graph"
4. Scegli "Autorizzazioni applicazione"
5. Aggiungi questi permessi:
   - `OnlineMeetings.ReadWrite.All` - Per creare e gestire meeting
   - `OnlineMeetingArtifact.Read.All` - Per leggere i report presenze (CRITICO!)
   - `User.Read.All` - Per mappare utenti Teams con Moodle
   - `Calendars.ReadWrite` - Per integrazione calendario (opzionale)
   
   **IMPORTANTE**: Il permesso `OnlineMeetingArtifact.Read.All` è essenziale per il tracciamento presenze e richiede privilegi elevati. Potrebbe richiedere approvazione speciale dall'amministratore Azure AD.

6. Clicca "Concedi consenso amministratore"

### 3. Creare il client secret

1. Vai su "Certificati e segreti"
2. Clicca "Nuovo segreto client"
3. Aggiungi descrizione e scadenza
4. **Copia subito il valore del segreto** (non sarà più visibile)

### 4. Ottenere gli ID necessari

Dalla pagina "Panoramica" dell'app, copia:
- **ID applicazione (client)**
- **ID directory (tenant)**

## Configurazione in Moodle

1. Vai su "Amministrazione del sito" → "Plugin" → "Moduli attività" → "Teams Meeting"
2. Inserisci:
   - **Tenant ID**: ID directory copiato da Azure
   - **Client ID**: ID applicazione copiato da Azure
   - **Client Secret**: Segreto client creato

3. Configura le impostazioni predefinite secondo le tue preferenze

## Utilizzo

### Per i docenti

1. **Creare un meeting**
   - Nel corso, attiva modifica
   - Aggiungi attività → Teams Meeting
   - Configura nome, data/ora, opzioni
   - Salva

2. **Visualizzare presenze**
   - Apri l'attività Teams Meeting
   - Clicca "Visualizza report presenze"
   - Esporta in vari formati se necessario

3. **Sincronizzare presenze**
   - Nel report presenze, clicca "Sincronizza presenze"
   - I dati vengono aggiornati da Microsoft Teams

### Per gli studenti

1. Accedi al corso
2. Clicca sull'attività Teams Meeting
3. Clicca "Partecipa al meeting" quando disponibile
4. Verrai reindirizzato a Teams

## Tracciamento presenze

Il plugin traccia automaticamente:
- Ora di ingresso al meeting
- Ora di uscita
- Durata totale di partecipazione
- Numero di sessioni (se l'utente entra/esce più volte)
- Percentuale di presenza rispetto alla durata del meeting

### Configurazione durata minima

Puoi impostare una durata minima di partecipazione per considerare uno studente "presente". 
Default: 10 minuti.

## Risoluzione problemi

### Errore "Impossibile ottenere il token di accesso"

Verifica:
1. Client ID e Tenant ID corretti
2. Client secret non scaduto
3. Permessi API concessi correttamente

### Meeting non si crea

Controlla:
1. L'utente ha una licenza Teams valida
2. I permessi dell'app Azure sono corretti
3. Il consenso amministratore è stato concesso
4. Attiva la modalità debug e visita <code>/mod/teamsmeetings/debug_teams.php</code> per un test rapido
5. In caso di errore, se abilitato il fallback calendario, controlla che il permesso Calendars.ReadWrite sia presente

### Presenze non sincronizzate

Possibili cause:
1. Il meeting non è ancora terminato
2. Report non ancora generato da Teams (può richiedere fino a 48 ore)
3. Permessi insufficienti per leggere i report
4. **NOTA**: L'API per i report presenze utilizza l'endpoint Beta di Microsoft Graph, che potrebbe avere limitazioni o cambiamenti

### Utenti non riconosciuti nel report

Il plugin cerca di mappare utenti Teams con utenti Moodle tramite email. Se alcuni partecipanti non vengono riconosciuti:
1. Verifica che usino la stessa email su Teams e Moodle
2. Gli utenti esterni (guest) potrebbero non essere mappabili automaticamente
3. Controlla la tabella `teamsmeetings_unmatched_users` per vedere gli utenti non riconosciuti

## Cron Jobs

Il plugin usa il cron di Moodle per:
- Sincronizzare automaticamente le presenze
- Inviare promemoria meeting
- Pulire dati vecchi

Frequenza consigliata: ogni 5 minuti

## Privacy e GDPR

Il plugin:
- Memorizza solo dati necessari per il funzionamento
- Supporta le API privacy di Moodle
- Permette l'export/cancellazione dati utente
- Non condivide dati con terze parti (oltre Microsoft per Teams)

## Supporto

Per problemi o domande:
1. Controlla la documentazione
2. Verifica i log di Moodle
3. Apri una issue su GitHub

## Esecuzione dei test

I test unitari devono essere eseguiti dalla root di un'installazione Moodle. In
particolare occorre inizializzare l'ambiente di PHPUnit e lanciare i test del
plugin con i seguenti comandi:

```bash
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit mod/teamsmeetings
```

## Licenza

GPL v3 o successiva

## Crediti

Sviluppato da Michele Dipace (Miki)

## ⚠️ Avviso Importante

**Prima di utilizzare in produzione:**
1. Testa approfonditamente in un ambiente di staging
2. Verifica che i permessi API siano configurati correttamente
3. Assicurati che il report presenze funzioni (potrebbe richiedere permessi Azure AD elevati)
4. Monitora i log per eventuali errori API durante le prime sincronizzazioni
5. Considera che l'API Beta di Microsoft potrebbe subire modifiche

## Changelog

### v1.0.0 (2024-06-16)
- Prima release
- Creazione meeting Teams
- Tracciamento presenze
- Report dettagliati
- Export dati in vari formati (CSV, Excel, PDF)
