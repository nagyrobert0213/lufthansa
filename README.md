# Lufthansa - Nagy Róbert

## Installation
Docker compose command should start everything:
```
docker compose -f docker-compose.yml -p lufthansa up -d
```

If the migration didn't run:
```
php bin/console doctrine:migration:migrate
```

## Endpoints
```
GET - /api/users
GET - /api/users/{id}
POST - /api/users
```

If any question comes up, feel free to ask me.