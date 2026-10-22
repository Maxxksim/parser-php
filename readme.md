PHP 8.5 MySQL 8.4 Redis 8.8

Steps to start the project:

Clone the project to yourself  
Copy .env.example to .env in root directory  
Change environment variables if you want your settings of the parser:        
Before start parsing you must configure your proxy:  
Switch WITH_PROXY in true (if it's false) and fill PROXY_USERNAME, PROXY_PASSWORD then put some proxies in array of proxies which is request config    
You can find the config in the next path: Config/request.php  

LOG_MODE = set file if you want to get logs to Logs/logs.log otherwise set debug to get log to STDOUT  
NUMBER_PROCESSES = set the number of processes which will parse otherwise only one process will do it  

Open a terminal in root directory  

For Windows system via Docker:     
Start docker if you didn't it before  

1. Set DB_HOST in db in your .env file  
2. Set REDIS_HOST in redis in your .env file  
Then enter the commands in the terminal:
3. docker compose up app -d
4. docker compose run --rm migrate
5. docker compose run --rm start-parser

For Unix systems:

Enter the commands in the terminal:  
1. composer install   
2. docker compose up db redis -d    
3. php ./Database/migrate.php    
4. php startParser.php    
