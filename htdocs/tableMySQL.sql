CREATE DATABASE IF NOT EXISTS DefCodesDb;
USE DefCodesDb;

CREATE TABLE IF NOT EXISTS DefCodesInfo (
  id INT(6) NOT NULL auto_increment PRIMARY KEY,
  def VARCHAR(3) NOT NULL,
  num_s VARCHAR(7) NOT NULL,
  num_e VARCHAR(7) NOT NULL,
  region VARCHAR(100),
  operator VARCHAR(100),
  fd date,
  td date
) DEFAULT CHARSET=UTF8;