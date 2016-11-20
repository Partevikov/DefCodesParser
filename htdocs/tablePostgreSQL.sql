CREATE SEQUENCE IF NOT EXISTS autoincrement_id;

CREATE TABLE IF NOT EXISTS DefCodesInfo (
  id INT NOT NULL DEFAULT nextval('autoincrement_id') PRIMARY KEY,
  def VARCHAR(3) NOT NULL,
  num_s VARCHAR(7) NOT NULL,
  num_e VARCHAR(7) NOT NULL,
  region VARCHAR(100),
  operator VARCHAR(100),
  fd date,
  td date
);