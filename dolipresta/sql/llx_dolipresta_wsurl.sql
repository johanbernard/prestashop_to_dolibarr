-- ===================================================================
-- 2015 PJ CONSEIL
--
-- NOTICE OF LICENSE
--
-- This source file is subject to License
-- You may not distribute this module even for free
--
-- @author    PJ CONSEIL
-- @copyright 2014 PJ CONSEIL
-- @license   NoLicence
-- @version   RC2
-- ===================================================================

create table llx_dolipresta_wsurl
(
  rowid						integer AUTO_INCREMENT PRIMARY KEY,
  url						varchar(255),
  wskey						varchar(50),
  trigram					varchar(3)
)ENGINE=innodb;

create table llx_dolipresta_prestashop_statut
(
  id						integer PRIMARY KEY,
  libelle					varchar(255)
)ENGINE=innodb;

insert into llx_dolipresta_prestashop_statut (id, libelle) VALUES (0, '');

create table llx_dolipresta_dolibarr_statut
(
  id						integer PRIMARY KEY,
  libelle					varchar(255),
  id_prestashop_statut		integer 
)ENGINE=innodb;

--insert into llx_dolipresta_dolibarr_statut (id, libelle, id_prestashop_statut) VALUES (1, 'StatusOrderDraft', 0);
insert into llx_dolipresta_dolibarr_statut (id, libelle, id_prestashop_statut) VALUES (2, 'StatusOrderValidated', 0);
insert into llx_dolipresta_dolibarr_statut (id, libelle, id_prestashop_statut) VALUES (3, 'StatusOrderSent', 0);
insert into llx_dolipresta_dolibarr_statut (id, libelle, id_prestashop_statut) VALUES (4, 'StatusOrderToBill', 0);
insert into llx_dolipresta_dolibarr_statut (id, libelle, id_prestashop_statut) VALUES (5, 'StatusOrderProcessed', 0);
insert into llx_dolipresta_dolibarr_statut (id, libelle, id_prestashop_statut) VALUES (6, 'StatusOrderCanceled', 0);
