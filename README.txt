=== PayJoe Belegschnittstelle für WooCommerce ===
Contributors: payjoe, twendt
Tags: payjoe, Rechnung, CTW, oscware, Germanized, invoices, invoice, WooCommerce, German Market, market press
Requires at least: 5.7
Requires PHP: 7.3
Tested up to: 6.0.1
Stable tag: 1.10.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



== Description ==

PayJoe hilft Ihnen bei Ihrer Buchhaltung und gleicht Ihre Zahlungen von z.B. AMAZON, PayPal, Klarna usw. mit den Rechnungen ab.

Kostenfreier Testzugang unter <a href="https://www.payjoe.de" target="_blank">www.payjoe.de</a>

**Funktionen**

* automatischer, zeitgesteuerter Upload der Belege zu PayJoe

* Manueller Sofortupload der Belege

* Support für WooCommerce Germanized

* Support für WooCommerce PDF Invoices & Packing Slips

* Support für German Market

<hr>
PayJoe Wordpress Plugin ursprünglich entwickelt von <a href="https://weslink.de" target="_blank">WESLINK</a>.


== Installation ==

Installieren Sie dieses Plugin und tragen Sie die API Daten von PayJoe ein.

**Voraussetzungen**

* https://wordpress.org/plugins/woocommerce/

== Frequently Asked Questions ==

= Wo finde ich die Debug Logs=

Wenn Sie in den Einstellungen "Protokollierung" aktiviert haben (Enable) werden Die Logdateien in dem folgenden Ordner abgelegt:
wp-content/uploads/payjoe/

= Wie erhalte ich Support =

Schreiben Sie eine E-Mail an info@payjoe.de oder [erstellen Sie ein Ticket](https://hilfe.payjoe.de/online-haendler/Content/topics_payjoe/support/ticket_erstellen.htm) aus PayJoe heraus.

== Screenshots ==

1. Übersicht der Einstellungen

== Changelog ==

= 1.10.1 =
* Entferne leere Javascript Datei.

= 1.10.0 =
* Lese Amazon Bestellnummer von "WP-Lister for Amazon" aus und übertrage sie in Referenzfeld 3.
* Lese Amazon Pay Capture und Refund IDs des "WooCommerce Amazon Pay" Plugins aus und übertrage sie in Referenzfeld 3.

= 1.9.2 =
* Behebe Fehler, wenn kein Rechnungsobjekt zurückgegeben wird.
* Verbesserte Fehlerausgabe und Handling.

= 1.9.1 =
* Greife bei Rückerstattungen für manche Informationen auf die originale Bestellung zurück.

= 1.9.0 =
* Lese Bestellinformationen (Amazon Bestellnummer, eBay Payments Bestellnummer & Käufer, ...) aus magnalister aus.

= 1.8.1 =
* Behebe Fehler in der Bestellansicht

= 1.8.0 =
* Viele interne Anpassungen aufgrund Code Reviews.
* Lese eBay Bestellnummer von "WP-Lister for eBay" aus und übertrage sie in Referenzfeld 3.

= 1.7.2 =
* Interne Anpassungen

= 1.7.1 =
* Behebe Fehler in der "PDF Invoices & Packing Slips" Integration.

= 1.7.0 =
* Greife auf mehr Steuersätze in den Bestellungen zurück, statt diese zu berechnen.
* Teile Steuern der Versandkosten anteilig auf.
* Übertrage Gebühren.
* Neue Funktionen zum Übertragen einzelner Bestellungen zu PayJoe.

= 1.6.2 =
* Zuverlässigere CronJob-Ausführung

= 1.6.1 =
* Behebe Problem, wenn Order ID nicht der Post ID entspricht.
  Wenn das Plugin keine neuen Belege mehr überträgt oder Sie Belege in PayJoe vermissen, dann stoßen Sie nach dem Update eine erneute Übertragung an. Passen Sie davor eventuell das Startdatum an.

= 1.6.0 =
* German Market: Übertrage Stornos und behebe Probleme mit dem Übertragen
* WCPDF: Überarbeite Code

== Upgrade Notice ==

= 1.7.0 =
* Bessere Bestimmung der Steuersätze von Versandkosten.
* PHP < 7.3 wird nicht mehr unterstützt.