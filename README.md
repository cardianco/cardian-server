## Cardian Server
Server side of Cardian app.<br>

APIs list:
+ GraphQL API
+ Socket Server

## Usage
### Clone
```bash
git clone --recursive https://github.com/SMR76/cardian-server.git
[ -d "cardian-server" ] && cd "cardian-server" && composer update
```
## To-Do
+ [ ] Add Support for `PostgreSQL`.
+ [ ] Add Table for user information.
+ [ ] Instead of purge events, use partitions.
+ [ ] Request limit per IP.

## Dependencies
+ [GraphQL](https://webonyx.github.io/graphql-php) (*MIT license*)
+ [WorkerMan](https://github.com/walkor/workerman) (*MIT license*)