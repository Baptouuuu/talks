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

### Domain Driven Design

^ résoud problèmes maintenance

---

Un `User` contient des `Address`

^ les adresses ne peuvent pas être partagées

---

```php
class User
{
    public function __construct(
        private Address $address,
    ) {}
}
```

```php
class Address
{
    public function __construct(
        private string $street,
        private string $code,
        private string $city,
    ) {}
}
```

---

[.code-highlight: 3]

```php
class User
{
    private int $id;

    public function __construct(
        private Address $address,
    ) {}
}
```
[.code-highlight: 3]

```php
class Address
{
    private int $id;

    public function __construct(
        private string $street,
        private string $code,
        private string $city,
    ) {}
}
```

^ problème : chaque objet est obligé d'avoir un id pour identifier la ligne en bdd

---

[.code-highlight: 6]

```php
class User
{
    private int $id;

    public function __construct(
        private Address $address,
    ) {}
}
```
[.code-highlight: 0]

```php
class Address
{
    private int $id;

    public function __construct(
        private string $street,
        private string $code,
        private string $city,
    ) {}
}
```

^ problème : adresse peut être partagée

---

[.code-highlight: 4, 7-9, 11]

```php
class User
{
    private int $id;
    private Address $address;

    public function __construct(
        string $street,
        string $code,
        string $city,
    ) {
        $this->address = new Address($this, $street, $code, $city);
    }
}
```
[.code-highlight: 6]

```php
class Address
{
    private int $id;

    public function __construct(
        User $user,
        private string $street,
        private string $code,
        private string $city,
    ) {}
}
```

^ problème : référence circulaire, transition problème de mémoire

---

---

```php
use Doctrine\ORM\EntityManagerInterface;

function (EntityManagerInterface $manager) {
    $entities = $manager
        ->getRepository(User::class)
        ->findAll();
}
```

^ problème : fuite mémoire

---

[.code-highlight: 7-12]

```php
use Doctrine\ORM\EntityManagerInterface;

function (EntityManagerInterface $manager) {
    $repository = $manager->getRepository(User::class);
    $count = $repository->count();

    for ($offset = 0; $offset < $count; $offset += 100) {
        $entities = $repository->findBy(
            limit: 100,
            offset: $offset,
        );
    }
}
```

^ problème : fuite mémoire

---

[.code-highlight: 8]

```php
use Doctrine\ORM\EntityManagerInterface;

function (EntityManagerInterface $manager) {
    $repository = $manager->getRepository(User::class);
    $count = $repository->count();

    for ($offset = 0; $offset < $count; $offset += 100) {
        $manager->clear();
        $entities = $repository->findBy(
            limit: 100,
            offset: $offset,
        );
    }
}
```

^ problème : gérer localement un état global

---

^ "Ces problèmes, et il en existe d'autres, sont inextricables du design des orms actuels. Et si on repensait ce design ?"

---

## Arrive Formal !

^ ORM orienté DDD

---

```sh
composer require formal/orm
```

---

```php
use Formal\ORM\Id;

final readonly class User
{
    /** @param Id<self> $id */
    public function __construct(
        private Id $id,
        private Address $address,
    ) {}
}
```

```php
final readonly class Address
{
    public function __construct(
        private string $street,
        private string $code,
        private string $city,
    ) {}
}
```

^ Immuable

---

```php
$address = new Address('somewhere', '12345', 'Somewhereville');
$user1 = new User(
    Id::new(User::class),
    $address,
);
$user2 = new User(
    Id::new(User::class),
    $address,
);
```

^ 2 adresses persistées

---

[.code-highlight: 3-5, 8-9]

```php
use Innmind\Immutable\Either;

$repository = $manager->repository(User::class);
$manager->transactional(
    static function() use ($repository) {
        $user1 = ...;
        $user2 = ...;
        $repository->put($user1);
        $repository->put($user2);

        return Either::right(null);
    },
);
```

^ transaction permet de faire les appels sql directement

---

```php
$manager
    ->repository(User::class)
    ->all()
    ->foreach(static fn(User $user) => doSomething($user));
```

^ Lazy + memory safe

---

[.code-highlight: 4-5]

```php
$manager
    ->repository(User::class)
    ->all()
    ->drop(1_000)
    ->take(100)
    ->foreach(static fn(User $user) => doSomething($user));
```

---

^ blague que c'est la slide de la config

---

## Avantages

---

### Sécurité

^ mémoire, impossible de mal l'utiliser

---

### No SQL

---

```php
use Formal\ORM\Specification\Entity;
use Innmind\Specification\Property;
use Innmind\Specification\Sign;

$manager
    ->repository(User::class)
    ->matching(
        Entity::of('address', Property::of(
            'city',
            Sign::equality,
            'Lyon',
        )),
    )
    ->foreach(static fn(User $user) => doSomething($user));
```

---

### Stockage

---

```php
use Formal\ORM\Manager;
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Url;

$manager = Manager::sql(
    Factory::build()->remote()->sql(
        Url::of('mysql://user:password@localhost:3306/database'),
    ),
);
```

^ Ça c'est attendu, mysql et postgres

---

```php
use Formal\ORM\Manager;
use Innmind\OperatingSystem\Factory;
use Innmind\Url\Path;

$manager = Manager::filesystem(
    Factory::build()->filesystem()->mount(
        Path::of('some/directory/'),
    ),
);
```

^ FS concret, en mémoire, S3

---

```php
use Formal\ORM\Manager;
use Innmind\Filesystem\Adapter\InMemory;

$manager = Manager::filesystem(
    InMemory::emulateFilesystem(),
);
```

---

```php
use Formal\ORM\Manager;
use Innmind\OperatingSystem\Factory;
use Innmind\S3
use Innmind\Url\Url;
use Innmind\Url\Path;

$os = Factory::build();
$manager = Manager::filesystem(
    S3\Filesystem\Adapter::of(
        S3\Factory::of($os)->build(
            Url::of('https://user:password@bucket.s3.region-name.scw.cloud/'),
            S3\Region::of('region-name'),
        ),
    ),
);
```

---

```php
use Formal\ORM\Manager;
use Formal\ORM\Adapter\Elasticsearch;
use Innmind\Filesystem\Adapter\InMemory;

$manager = Manager::of(
    Elasticsearch::of(
        Factory::build()->remote()->http(),
    ),
);
```

---

![](pbt.png)

Les trois ont exactement le même comportement

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

![](doc.png)

### Et plus

![inline](qr.png)

---

## Questions

![inline](open-feedback.png)

Twitter @Baptouuuu

Github @Baptouuuu/talks
