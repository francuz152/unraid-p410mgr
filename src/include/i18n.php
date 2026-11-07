<?php
/* p410mgr — tłumaczenia / translations (EN default, PL) */

function p410_lang(): string {
  static $lang = null;
  if ($lang !== null) return $lang;
  $cfg = @parse_ini_file('/boot/config/plugins/p410mgr/p410mgr.cfg', false, INI_SCANNER_RAW) ?: [];
  $sel = $cfg['LANG'] ?? 'auto';
  if ($sel === 'en' || $sel === 'pl') return $lang = $sel;
  $dyn = @parse_ini_file('/boot/config/plugins/dynamix/dynamix.cfg', true, INI_SCANNER_RAW) ?: [];
  $loc = $dyn['display']['locale'] ?? '';
  return $lang = (stripos($loc, 'pl') === 0) ? 'pl' : 'en';
}

function p410_strings(): array {
  return [
    /* backend */
    'only_post'      => ['Only POST requests are allowed.', 'Dozwolone tylko POST.'],
    'no_bin'         => ['%s not found. Reinstall the plugin.', 'Nie znaleziono %s. Zainstaluj plugin ponownie.'],
    'no_ctrl'        => ['hpssacli did not find any Smart Array controller.', 'hpssacli nie znalazł żadnego kontrolera Smart Array.'],
    'bad_slot'       => ['Invalid controller slot.', 'Nieprawidłowy slot kontrolera.'],
    'bad_params'     => ['Invalid parameters.', 'Nieprawidłowe parametry.'],
    'led_done'       => ['LED %s: %s', 'Dioda %s: %s'],
    'bad_drive'      => ['Invalid drive.', 'Nieprawidłowy dysk.'],
    'no_serial'      => ['Could not read the serial number.', 'Nie udało się odczytać numeru seryjnego.'],
    'no_cciss'       => ['Drive not found via cciss (the controller needs at least one visible volume).', 'Nie znaleziono dysku przez cciss (kontroler musi mieć przynajmniej jeden widoczny wolumen).'],
    'not_unassigned' => ['Drive %s is not unassigned.', 'Dysk %s nie jest nieprzypisany (unassigned).'],
    'create_err'     => ["Error creating volume:\n%s", "Błąd tworzenia wolumenu:\n%s"],
    'created'        => ['Created LD %s (RAID0) from %s', 'Utworzono LD %s (RAID0) z %s'],
    'created_nodev'  => [' (device not visible yet – refresh in a moment)', ' (urządzenie jeszcze niewidoczne – odśwież za chwilę)'],
    'bad_ld'         => ['Invalid volume number.', 'Nieprawidłowy numer wolumenu.'],
    'confirm_bad'    => ['Confirmation does not match the volume number.', 'Potwierdzenie nie zgadza się z numerem wolumenu.'],
    'ld_missing'     => ['Volume %s does not exist.', 'Wolumen %s nie istnieje.'],
    'refused'        => ['Refused: %s is %s.', 'Odmowa: %s jest %s.'],
    'in_unraid'      => ['assigned in Unraid as %s', 'przypisany w Unraid jako %s'],
    'mounted'        => ['mounted', 'zamontowany'],
    'used_by'        => ['used by %s', 'używany przez %s'],
    'delete_err'     => ["Error deleting volume:\n%s", "Błąd usuwania:\n%s"],
    'deleted'        => ['Deleted LD %s', 'Usunięto LD %s'],
    'cache_done'     => ['LD %s cache: %s', 'Cache LD %s: %s'],
    'no_data'        => ['No data.', 'Brak danych.'],
    'saved'          => ['Settings saved.', 'Zapisano ustawienia.'],
    'set_broker'     => ['Set and save the broker address first.', 'Najpierw ustaw i zapisz adres brokera.'],
    'mqtt_test_ok'   => ['Connected to %s and published %s/test', 'Połączono z %s i wysłano %s/test'],
    'baseline_reset' => ['SMART counter baseline reset – it will be recorded on the next check.', 'Zresetowano punkt odniesienia liczników SMART – zostanie ustawiony przy następnym sprawdzeniu.'],
    'unknown_action' => ['Unknown action.', 'Nieznana akcja.'],
    'bay_state'      => ['bay %s: %s', 'kieszeń %s: %s'],
    /* problems */
    'pr_ctrl'        => ['Controller slot %s: %s', 'Kontroler slot %s: %s'],
    'pr_cache'       => ['Controller cache: %s', 'Cache kontrolera: %s'],
    'pr_batt'        => ['Cache battery: %s', 'Bateria cache: %s'],
    'pr_ld'          => ['LD %s (%s): %s', 'LD %s (%s): %s'],
    'pr_label'       => ['Bay %s (%s)', 'Kieszeń %s (%s)'],
    'pr_state'       => ['state: %s', 'stan: %s'],
    'pr_pending'     => ['pending sectors: %s', 'oczekujące sektory: %s'],
    'pr_offline'     => ['uncorrectable sectors: %s', 'nienaprawialne sektory: %s'],
    'pr_increase'    => ['%s increased: %s → %s', '%s wzrosły: %s → %s'],
    'c_defects'      => ['grown defects', 'grown defects'],
    'c_realloc'      => ['reallocated sectors', 'realokowane sektory'],
    'c_uncorr'       => ['uncorrected errors', 'błędy nienaprawione'],
    'pr_temp'        => ['temperature %s °C (threshold %s °C)', 'temperatura %s °C (próg %s °C)'],
    'mqtt_conn_fail' => ['Connection to %s:%s failed: %s', 'Połączenie z %s:%s nieudane: %s'],
    'mqtt_rejected'  => ['Broker rejected the connection', 'Broker odrzucił połączenie'],
    'mqtt_noack'     => [' (no CONNACK)', ' (brak CONNACK)'],
    'mqtt_rc1'       => ['unacceptable protocol version', 'zła wersja protokołu'],
    'mqtt_rc2'       => ['client identifier rejected', 'odrzucony identyfikator'],
    'mqtt_rc3'       => ['server unavailable', 'serwer niedostępny'],
    'mqtt_rc4'       => ['bad username or password', 'zły login lub hasło'],
    'mqtt_rc5'       => ['not authorized', 'brak autoryzacji'],
    'mqtt_rcx'       => ['code %s', 'kod %s'],
    'ntf_problem'    => ['P410: problem detected (%s)', 'P410: wykryto problem (%s)'],
    'ntf_resolved'   => ['P410: problems resolved', 'P410: problemy ustąpiły'],
    'ha_problem'     => ['Problem', 'Problem'],
    'ha_controller'  => ['Controller', 'Kontroler'],
    'ha_cache'       => ['Cache', 'Cache'],
    'ha_battery'     => ['Cache battery', 'Bateria cache'],
    'ha_maxtemp'     => ['Max drive temperature', 'Maks. temperatura dysków'],
    'ha_bay_temp'    => ['Bay %s temperature', 'Kieszeń %s temperatura'],
    'ha_bay_status'  => ['Bay %s status', 'Kieszeń %s stan'],
    /* GUI */
    'js_refresh'     => ['Refresh', 'Odśwież'],
    'js_loading'     => ['Loading…', 'Ładowanie…'],
    'js_reading'     => ['Reading controller…', 'Odczyt kontrolera…'],
    'js_read_at'     => ['Read at: %s', 'Odczytano: %s'],
    'js_bad_resp'    => ['Invalid server response:', 'Błędna odpowiedź serwera:'],
    'js_error'       => ['Error', 'Błąd'],
    'js_no_drives'   => ['No drives', 'Brak dysków'],
    'js_bay'         => ['Bay', 'Kieszeń'],
    'js_drive'       => ['Drive', 'Dysk'],
    'js_type'        => ['Type', 'Typ'],
    'js_size'        => ['Size', 'Rozmiar'],
    'js_model'       => ['Model', 'Model'],
    'js_serial'      => ['Serial', 'Nr seryjny'],
    'js_temp'        => ['Temp.', 'Temp.'],
    'js_state'       => ['State', 'Stan'],
    'js_volume'      => ['Volume', 'Wolumen'],
    'js_cache'       => ['Cache', 'Cache'],
    'js_unraid'      => ['Unraid', 'Unraid'],
    'js_actions'     => ['Actions', 'Akcje'],
    'js_detached'    => ['detached', 'odłączone'],
    'js_detached_t'  => ['Device detached from the OS', 'Urządzenie odłączone od systemu'],
    'js_led_on'      => ['LED on', 'LED wł.'],
    'js_led_on_t'    => ['Turn the locate LED on', 'Zapal diodę'],
    'js_led_off'     => ['off', 'wył.'],
    'js_led_off_t'   => ['Turn the locate LED off', 'Zgaś diodę'],
    'js_create'      => ['Create RAID0', 'Utwórz RAID0'],
    'js_delete'      => ['Delete LD', 'Usuń LD'],
    'js_in_unraid'   => ['The drive is assigned in Unraid', 'Dysk jest przypisany w Unraid'],
    'js_cache_on'    => ['Enable cache', 'Włącz cache'],
    'js_cache_off_t' => ['Controller cache is disabled (%s) – replace the battery first', 'Cache kontrolera jest wyłączony (%s) – najpierw wymień baterię'],
    'js_smart_read'  => ['Reading SMART %s (the first time may take a few seconds)…', 'Odczyt SMART %s (pierwszy raz może potrwać kilkanaście sekund)…'],
    'js_close'       => ['Close', 'Zamknij'],
    'js_create_q'    => ["Create a new RAID0 volume from drive %s?\nData on this drive may be lost.", "Utworzyć nowy wolumen RAID0 z dysku %s?\nDane na tym dysku mogą zostać utracone."],
    'js_creating'    => ['Creating volume…', 'Tworzenie wolumenu…'],
    'js_delete_q'    => ["DELETE volume LD %s%s.\nAll data on this drive will become inaccessible.\n\nTo confirm, type the volume number: %s", "USUNIĘCIE wolumenu LD %s%s.\nWszystkie dane na tym dysku będą niedostępne.\n\nAby potwierdzić, wpisz numer wolumenu: %s"],
    'js_cancelled'   => ['Cancelled – the number does not match.', 'Anulowano – numer się nie zgadza.'],
    'js_deleting'    => ['Deleting volume…', 'Usuwanie wolumenu…'],
    'js_mon_title'   => ['SMART monitoring & MQTT', 'Monitoring SMART i MQTT'],
    'js_last'        => ['Last check:', 'Ostatnie sprawdzenie:'],
    'js_never'       => ['never', 'brak'],
    'js_problems'    => ['PROBLEMS: %s', 'PROBLEMY: %s'],
    'f_lang'         => ['Language', 'Język'],
    'f_lang_auto'    => ['Automatic (Unraid)', 'Automatyczny (Unraid)'],
    'f_mon'          => ['Periodic check', 'Cykliczne sprawdzanie'],
    'f_on'           => ['Enabled', 'Włączone'],
    'f_off'          => ['Disabled', 'Wyłączone'],
    'f_yes'          => ['Yes', 'Tak'],
    'f_no'           => ['No', 'Nie'],
    'f_interval'     => ['Interval (minutes)', 'Co ile minut'],
    'f_temp'         => ['Drive temperature threshold (°C)', 'Próg temperatury dysku (°C)'],
    'f_standby'      => ["Don't wake sleeping drives", 'Nie budź uśpionych dysków'],
    'f_ign_cache'    => ['Ignore disabled controller cache', 'Ignoruj wyłączony cache kontrolera'],
    'f_ign_batt'     => ['Ignore cache battery state', 'Ignoruj stan baterii cache'],
    'f_notify'       => ['Unraid notifications', 'Powiadomienia Unraid'],
    'f_mqtt'         => ['MQTT', 'MQTT'],
    'f_host'         => ['Broker (IP / host)', 'Broker (IP / host)'],
    'f_host_ph'      => ['e.g. 192.168.1.10', 'np. 192.168.1.10'],
    'f_port'         => ['Port', 'Port'],
    'f_user'         => ['Username', 'Użytkownik'],
    'f_pass'         => ['Password', 'Hasło'],
    'f_topic'        => ['Base topic', 'Temat bazowy'],
    'f_disc'         => ['Home Assistant discovery', 'Home Assistant discovery'],
    'f_disc_prefix'  => ['Discovery prefix', 'Prefiks discovery'],
    'b_save'         => ['Save', 'Zapisz'],
    'b_test'         => ['Test MQTT', 'Test MQTT'],
    'b_check'        => ['Check now', 'Sprawdź teraz'],
    'b_baseline'     => ['Accept current SMART counters', 'Akceptuj obecne liczniki SMART'],
    'b_baseline_t'   => ['Use the current SMART counters as the reference', 'Przyjmij obecne liczniki SMART jako punkt odniesienia'],
    'js_test_run'    => ['Testing MQTT (save your changes first)…', 'Test MQTT (najpierw zapisz zmiany)…'],
    'js_checking'    => ['Checking all drives (SMART) – this can take up to a minute…', 'Sprawdzanie wszystkich dysków (SMART) – może potrwać do minuty…'],
    'js_check_done'  => ['Check finished.', 'Sprawdzenie zakończone.'],
    'js_baseline_q'  => ['Accept the current SMART counters (reallocations, defects, errors) as normal?', 'Przyjąć obecne wartości liczników SMART (realokacje, defekty, błędy) jako normalne?'],
    /* dashboard */
    'd_open'         => ['Open Smart Array P410', 'Otwórz Smart Array P410'],
    'd_no_resp'      => ['No response', 'Brak odpowiedzi'],
    'd_all_ok'       => ['All OK', 'Wszystko OK'],
    'd_attention'    => ['Needs attention', 'Wymaga uwagi'],
    'd_controller'   => ['Controller', 'Kontroler'],
    'd_cache'        => ['Cache', 'Cache'],
    'd_battery'      => ['Battery', 'Bateria'],
    'd_drives'       => ['Drives / volumes', 'Dyski / wolumeny'],
    'd_maxtemp'      => ['Max temperature', 'Maks. temperatura'],
    'd_ignored'      => ['ignored', 'ignorowane'],
  ];
}

function p410_t(string $key, ...$args): string {
  static $s = null; $s ??= p410_strings();
  $txt = $s[$key][p410_lang() === 'pl' ? 1 : 0] ?? $key;
  return $args ? vsprintf($txt, $args) : $txt;
}

/* Słownik dla JS (tylko bieżący język) */
function p410_js_dict(): array {
  $i = p410_lang() === 'pl' ? 1 : 0; $o = [];
  foreach (p410_strings() as $k => $v) $o[$k] = $v[$i];
  return $o;
}
