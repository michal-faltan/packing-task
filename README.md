### init
- `printf "UID=$(id -u)\nGID=$(id -g)" > .env`
- `add credentials for BinPackingApi to .env under keys: "BIN_PACKING_USERNAME" and "BIN_PACKING_APIKEY"`
- `docker compose up -d --build`
- `docker compose run shipmonk-packing-app bash`
- `bin/migrations migrations:migrate`
- `bin/doctrine dbal:run-sql "$(cat data/packaging-data.sql)"`

### test run from cli
- `php run.php "$(cat sample.json)"`

### send POST request to http://localhost:8081/
- `body example:`
{
    "products": [
        {
            "id": 1,
            "width": 3.5,
            "height": 2.1,
            "length": 3.0,
            "weight": 4.0
        },
        {
            "id": 2,
            "width": 4.9,
            "height": 1.0,
            "length": 2.4,
            "weight": 9.9
        }
    ]
}

### adminer
- Open `http://localhost:8080/?server=mysql&username=root&db=packing`
- Password: secret
