CREATE TABLE users (
	id int NOT NULL AUTO_INCREMENT,
	username VARCHAR(255) NOT NULL,
    color VARCHAR(30) NOT NULL,
    
    PRIMARY KEY(id)
); 

CREATE UNIQUE INDEX name_user ON users (username);

CREATE TABLE credentials (
	id int NOT NULL AUTO_INCREMENT,
    user_id int NOT NULL,
	credential_id VARCHAR(255) UNIQUE NOT NULL,
    credential_data TEXT NOT NULL,
    
    PRIMARY KEY(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
