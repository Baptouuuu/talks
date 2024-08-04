autoscale: true
theme: Fira, 6

## Et si on repensait les ORMs ?

---

[.list: alignment(left)]

- Baptiste Langlade
- Architecte chez Efalia
- Lyon
- ~95 packages Open Source
- 10+ ans XP

---

## Les ORMs ont plus de 10 ans

---

[.list: alignment(left)]

## Il en existe plusieurs

- Doctrine
- Eloquent
- etc...

^ Data Mapper ou Active Record, transition next slide

---

## Ils ont tous le même concept

Transformer l'interface SQL en interface Objet

---

## Source des problèmes

Une abstraction doit réduire la complexité

^ Faire un parallèle avec les cartes

---

## Problème 1 : Graphe d'objet

^ Les foreign key sont représentées par des références

---

TODO exemple mermaid.js d'un graphe de plusieurs utilisateurs avec plusieurs adresses

à clusteriser par _table_

^ montrer que même les adresses doivent avoir des ids

---

TODO graphe pour montrer les différents types de relations

---

TODO exemple où 2 utilisateurs utilisent la même adresse

^ montrer le problème de cohérence en cas de suppression en cascade

---

---

## Problème 2 : Objets muables

^ Indispensable pour les relations et le compute du diff

---

Flush accidentel de données

---

`EntityManagerClosed` dès qu'il y a une erreur

^ Pour empêcher de persist des données incohérentes

---

Fuite mémoire

^ Ok en HTTP mais problématique en CLI vie longue

---

En somme on doit avoir conscience de l'état global de l'app pour ne pas faire d'erreur.

^ Au plus une app grossie au plus c'est complexe et difficile

---

---

## Solutions

^ PHP a beaucoup changé en 10 ans

---

### Domain Driven Design

^ Un aggregat assure la cohérence des objets qu'il référence

---

TODO graphe des utilisateurs où le partage d'une adresse est interdite

---

TODO graphe duplication de l'adresse

---

En somme on passe d'un graphe à un ensemble d'arbre

TODO visualiser avec un graphe clusterisé par aggregat au lieu de tables

---

### Programmation Fonctionnelle

^ Immuabilité

---

Exemple de code muable vs immuable

---

On n'utilise que des copies locales des objets

---

---

## Arrive Formal !

^ Show, don't tell !

---

```sh
composer require formal/orm
```

---

TODO exemple classe aggregate `User` avec juste un nom et une fonction `rename`

^ Immuable

---

TODO exemple de setup de `Manager` mais masquer le storage

^ Détail du storage plus tard

---

TODO exemple de persistence d'un `User`

---

TODO exemple de lister l'ensemble des `User`

^ Lazy + memory safe

---

TODO exemple de maj en masse

---

TODO exemple all + filter

^ Transition sur Specification

---

TODO exemple specification `HaveUsername::of(string)`

---

TODO exemple `matching`

---

TODO introduire `Address`

---

TODO modifier `User` pour montrer les entités, optionals et collections

---

Support de Value Objects possible

---

---

## Avantages

---

### Stockage

---

TODO exemple SQL

^ Ça c'est attendu

---

TODO exemple Filesystem

^ FS concret, en mémoire, S3

---

TODO exemple Elasticsearch

---

Les trois ont exactement le même comportement grâce au property based testing

^ Référence à la conf de 2023

---

### Compatibilité avec Innmind

---

[.list: alignment(left)]

- Génération de fichier
- Body requête/réponse HTTP
- Input de processus
- Envoi de messages AMQP
- Asynchrone

^ Mention qu'on génère des fichiers compressés de plusieurs Go chez Efalia

---

### Performance

---

~40% plus rapide que Doctrine sur lecture/écriture simple

---

### Et plus

TODO QR code vers documentation

---

## Questions

![inline](open-feedback.png)

Twitter @Baptouuuu

Github @Baptouuuu/talks
