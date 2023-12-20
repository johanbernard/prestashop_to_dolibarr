# Prestashop to Dolibarr

Synchronise Prestashop with Dolibarr.

This project contains two modules (one for Prestashop and one for Dolibarr). It lets you synchronise all of your PrestaShop stores with Dolibarr.

It allows synchronization of:
1. Your customers, intelligently sorted prospects or customers in Dolibarr.
2. Your categories.
3. Your products, stored in their categories with their picture, barcode and inventory sorted according to your warehouse.
4. Your orders with repercussion of internal links to your internal products and your internal customers into Dolibarr.
5. Bills with also impact internal links to your products and your customers into Dolibarr.
6. Orders statuts update from dolibarr to prestashop and from Prestashop to Dolibarr it's an exclusivity of our module
The module works by communicating with Dolibarr by imporved web-services. So you can have your PrestaShop and your Dolibarr on two different machines.

The Prestashop to Dolibarr Pro module is the only one to synchronize big data. This, with an auto-stop technology and intelligent recovery that will stop communicating himself before time-out of the server. And, with our technology you can sync all your data without server crashes and can catch without duplicates up as we can see in other similar modules. Moreover, when a phase shift occurs, a notification is inserted into PrestaShop.


## How to install

### French instructions

#### Cote Prestashop :

1. Desinstaller toute ancienne version de "Prestashop to Dolibarr"
2. Mettez les sources dans un prestashoptodolibarrpro et à l'interieur zippez le reprtoire dolipresta 
3. zippez le repertoire prestashoptodolibarrpro, vous devez avoir un zip prestashoptodolibarrpro.zip qui contient un unique reprtoire prestashoptodolibarrpro, qui lui même contient les sources et le fichier dolipresta.zip. 
4. installez ce module par l'importateur de module prestashop classique et suivez les instructions.


#### Cote Dolibarr :

5. Telecharger le module "dolipresta" via le lien fourni dans le module Prestashop To Dolibarr
6. Dezipper le fichier "dolipresta.zip" dans le repertoire "htdocs/" de votre Dolibarr.
7. Activer le module "dolipresta" dans Dolibarr (Accueil -> Configuration -> Modules -> onglet "Modules Interfaces" -> dolipresta)
8. Configurer la cle qui sera utilisee dans les Webservices dans la fenetre de configuration de "dolipresta" (symbole "outils" a droite, notez la : elle sera parametree dans Prestashop)

**Nota bene :** Si vous voulez exporter le stock des produits de Prestashop vers Dolibarr, n'oubliez pas de configurer un entrepot dans Dolibarr et de reporter son nom dans les parametres d'exportation du module "Prestashop to Dolibarr"


### English instructions

#### Prestashop side:

1. Uninstall any old version of "Prestashop to Dolibarr"
2. Put the sources in a prestashoptodolibarrpro directory and zip the dolipresta directory
3. Zip the prestashoptodolibarrpro directory, you must have a prestashoptodolibarrpro.zip file containing a unique prestashoptodolibarrpro directory, which itself contains the sources and the file dolipresta.zip
4. install this module by the classic prestashop module importer and follow the instructions.

#### Dolibarr side:

5. Download the module "dolipresta" via the link provided in the module Prestashop To Dolibarr
6. Unzip the file "dolipresta.zip" into the directory "htdocs /" of your Dolibarr.
7. Activate the "dolipresta" module in Dolibarr (Home -> Configuration -> Modules -> tab "Interfaces Modules" -> dolipresta)
8. Configure the key that will be used in the Webservices in the configuration window of "dolipresta" (symbol "tools" on the right, note: it will be set in Prestashop)

**Nota bene:** If you want to export PrestaShop products stocks to Dolibarr, do not forget to set up a warehouse in Dolibarr and set its name into appropriate export parameter in "Prestashop to Dolibarr" module.

## Get some informations

* WIKI : https://wiki.dolibarr.org/index.php/Module_PrestaShop_To_Dolibarr
* Thread on the French Dolibarr forum : https://www.dolibarr.fr/forum/t/prestashop-to-dolibarr/28190/166
* Thread on the English Dolibarr forum : https://www.dolibarr.org/forum/t/prestashop-to-dolibarr-pro-product-synronizing-problem/19363/9?u=romaincm
