## Cardian Server
Server side of Cardian app.<br>
Website: [*cardian.ir*](https://www.cardian.ir)

APIs list:
+ [x] GraphQL API
+ [ ] Socket Server *WIP*

## Usage
### Clone
```bash
git clone --recursive https://github.com/SMR76/cardian-server.git
[ -d "cardian-server" ] && cd "cardian-server" && composer update
```

## To-Do
+ [ ] Add Support for `PostgreSQL`.
+ [ ] Instead of purge events, use partitions.
+ [ ] Request limit per IP.
+ [x] Add Table for user information.

## Dependencies
+ [GraphQL](https://webonyx.github.io/graphql-php) (*MIT license*)
+ [WorkerMan](https://github.com/walkor/workerman) (*MIT license*)