#### Query:

+ Session information:
    ```graphql
    query{session{id authtoken accessUTC ip mac}}
    ```
+ Command history:
    ```graphql
    query{commandHistory(limit:1000,accessed:true){data}}
    ```
+ Status history:
    ```graphql
    query{statusHistory(limit:10,accessed:true){data}}
    ```
+ Last map polygon:
    ```graphql
    query{boundaries{id,utc}}
    ```
+ Get a list of a maximum of 100 entries of polygons, whose Ids are greater than 10:
    ```graphql
    query{boundaries(limit:100,start:10){id,poly{x,y}}}
    ```
+ Get a list of available field types:
    ```graphql
    query{fields{id,name}}
    ```
#### Mutations:
+ Add a new polygon:
    ```graphql
    mutation{createBoundary(stateId:1,poly:[{x:36.31,y:59.51},{x:36.32,y:59.50},{x:36.31,y:59.52}])}
    ```
+ Remove existing polygon:
    ```graphql
    mutation{removeBoundaries(idList: 2)}
    ```
+ Add a new status:
    ```graphql
    mutation{newStatus(fieldId:1, value:"{\"battery\":0.42101}")}
    ```