--creation de BDD 

create database culture_plateforme;


create table user_(
    id int PRIMARY key AUTO_INCREMENT,
    nom varchar(200) ,
    prenom varchar(200),
    email varchar(200),
    password varchar(500),
    post ENUM('auteur','admin','reader'),
    supprime tinyint DEFAULT 0
    
    );


    --
    CREATE table article(
    
    id int PRIMARY key AUTO_INCREMENT,
    titre varchar(100),
    description varchar(500),
    contenu text,
    id_auteur int ,
    statut enum ('nom confirme', 'confirme'),
    supprimer tinyint DEFAULT 0,
   FOREIGN KEY ( id_auteur)  REFERENCES user_(id)
    on DELETE  CASCADE 
    on UPDATE CASCADE
    
    );


    alter table user_
add COLUMN tel varchar(30);


alter table user_
add COLUMN matricule varchar(500);


--
ALTER table article 
add COLUMN image blob ;


    