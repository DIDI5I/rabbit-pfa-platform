Include:

base Repository
query(), fetchOne(), fetchMany()
why no raw PDO in child classes
LIMIT/OFFSET issue and solution

//*****************************//

Repository Pattern
1. Purpose
The Repository pattern abstracts database access and ensures that all queries are executed in a consistent and controlled way.
It prevents direct use of raw PDO in business logic.

2. Architecture Flow
Controller    ↓Service (optional)    ↓Repository    ↓Database (PDO)

3. Files Involved
app/Repositories/Repository.phpapp/Repositories/UserRepository.phpapp/Repositories/ProductRepository.phpapp/Repositories/CostRollupRepository.phpapp/Core/Database.phpapp/Core/App.php

4. Base Repository
All repositories extend:
class Repository
Core properties
protected PDO $connection;protected ?PDOStatement $statement;

5. Dependency Resolution
Connection is injected via:
$this->connection = App::resolve(Database::class)->connection();
This allows centralized database configuration.

6. Core Methods
query()
$this->query(string $sql, array $params = [])
Executes a prepared statement.
Returns:
static (allows method chaining)

fetchOne()
->fetchOne()
Returns:
Single row OR null

fetchMany()
->fetchMany()
Returns:
Array of rows OR empty array

exists()
->exists()
Returns:
true if at least one row existsfalse otherwise

7. Example Usage
$user = $this    ->query("SELECT * FROM users WHERE email = ?", [$email])    ->fetchOne();

8. Example — UserRepository
public function findByEmail(string $email): ?array{    return $this        ->query(            "SELECT id, name, email FROM users WHERE email = ? LIMIT 1",            [$email]        )        ->fetchOne();}

9. Example — ProductRepository
$rows = $this    ->query($sql, $params)    ->fetchMany();

10. Important Rules
Repositories DO:- execute SQL- return raw data- handle database interactionRepositories DO NOT:- read $_GET or $_POST- validate input- return HTTP responses- contain business logic

11. SQL Binding Behavior
All parameters passed to:
->query($sql, $params)
are bound automatically using:
$statement->execute($params);
Important implication
All values are bound as strings by default

12. LIMIT / OFFSET Issue
Problem:
LIMIT ? OFFSET ?
Results in:
LIMIT '10' OFFSET '0'
Which causes SQL errors.

Solution
LIMIT {$limit} OFFSET {$offset}
Safe because:
Values are cast to integersValues are bounded in ProductRequest

13. Data Normalization
After fetching data:
$row['id'] = (int) $row['id'];$row['stock_qty'] = (float) $row['stock_qty'];$row['is_active'] = (bool) $row['is_active'];
Ensures correct types in JSON response.

14. Tests
Test 1 — fetchOne()
Call findByEmail()
Expected:
Returns single user OR null

Test 2 — fetchMany()
GET /products
Expected:
Returns array of products

Test 3 — exists()
Check if user exists
Expected:
true or false

Test 4 — Parameter binding
Query with parameters
Expected:
No SQL injection possible

Test 5 — LIMIT handling
GET /products?limit=5
Expected:
Returns max 5 recordsNo SQL error

15. Edge Cases
Empty result setInvalid SQL syntaxLarge datasetsMissing parametersIncorrect type casting

16. Design Principles
Single responsibility: repository = database accessConsistency: all queries go through one interfaceSecurity: prepared statements prevent SQL injectionReusability: common query methods shared

17. Future Improvements
Add typed parameter binding (PDO::PARAM_INT)Add transaction supportAdd query loggingAdd caching layerAdd pagination helper inside repository