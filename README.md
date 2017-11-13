# Simple portal app

## DO ZROBIENIA
- ładniejszy html?
- ~~upload zdjęcia (obecnie jest url)~~
- ~~colorpicker przy wyborze kolorów w edycji strony~~
- ~~elementy w menu ładowane dynamicznie~~

## zastosowane technologie
- slim framework v3
- twig v1
- bootstrap v3
- jquery v3
- tinymce najnowszy

## Wymagania
- composer
- php 5.5+
- apache
- mysql

## Instalacja
- apache + php + mysql (we własnym zakresie)
- composer wg. instrukcji: [https://getcomposer.org/]()
- komenda "composer install"
- dodanie vhosta do apache (poniżej)
- dodanie do pliku /etc/hoosts wpisu 127.0.0.1 portal.dev
- stworzenie bazy "portal" i import struktury z pliku _db/portal.sql.zip

### vhost
```
<VirtualHost *:80>
    DocumentRoot "F:/Projects/portal/public" (UWAGA!!! ustawić tu własnąścieżkę)
    ServerName portal.dev
    ErrorLog "logs/portal-error.log"
    CustomLog "logs/portal-access.log" common
</VirtualHost>
```

### panel admina
[http://portal.dev/admin]()  

l: admin  
p: admin 

### Wytyczne do strony (z emaila)
    1.      W formie rozwijanej.
    2.      6 zakładek krótko opisujących realizowane projekty przez Koło: wprowadzenie (tło+ logo KNPZ +motto); konferencja WTwZ (Współczesne Trendy w Zarządzaniu); Festiwal Menadżerski (FM: kolor fioletowy); Blog Sfera Menadżera (SF: tutaj będzie odsyłacz do Bloga oraz FB https://www.facebook.com/sferamenedzera; http://www.sferamenedzera.pl/); Czasopismo (Journal of Modern Management Process: tutaj będzie odsyłacz do strony); kontakt; plus ewentualnie co Państwo uznacie za stosowne.
    3.      Prośba odnośnie Bloga SF, w tym momencie nieco „trąci myszką”.  Prośba o prostą aplikację mobilną, w taki  sposób aby można wpisywać krótkie informacje z linkami do oryginalnych źródeł. W historia wpisów i tagami.
    4.      Odsyłacz do FB Koła https://pl-pl.facebook.com/KNPZUEK/   oraz poszczególnych projektów (część już jest podana w nawiasach).
    5.      Prośba aby doprać kolory do poszczególnych projektów/ zakładek.
    6.      Bardzo prosty w obsłudze panel.
    7.      Ładna i czytelna; to w gratisie J.
