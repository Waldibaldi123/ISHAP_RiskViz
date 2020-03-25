##Zeitlinien Visualisierung von GmbH Scheinunternehmen geparst von Manz und bereichert mit GISA

Author: Daniel Walder
Email: dwalder@ucsd.edu

#File Übersicht:
fbnrList.json - beinhaltet Firmenbuchnummer und Rechtsbescheid Datum vom BMF von den zu visualisierenden Scheinunternehmen

MakeTimelines.php - liest fbnrList.json aus und ruft ManzParser.php sowie timeline.py auf

ManzParser.php - nimmt Firmenbuchnummer also Argument und parst das zugehörige Firmenbuch in ein class.JsonCompany Objekt und speichert dieses als Json File unter dem letzten Namen des Unternehmens im Ordner 'jsonFiles'

class.JsonCompany.php - das geparste Firmenbuch als Objekt

timeline.py - liest Manz und GISA Json Files aus und produziert/speichert eine Graphik im Ordner 'images'


#Verwendung:
In Ubuntu, navigiere zu Ordner und schreibe:

"php MakeTimelines.php"

Die zu visualisierenden Scheinunternehmen davor in fbnrList.json eintragen nach vorgegebenem Format
Manz wird automatisch geparst und in Json gespeichert
Die dazugehörigen 'Name'GISA.json Files müssen noch manuell geschrieben werden

Das Programm ist auf Ubuntu 18.04 getestet, für andere Plattformen könnte das matplotlib Back-end zu ändern sein (siehe imports timeline.py)
