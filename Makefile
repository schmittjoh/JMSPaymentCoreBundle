install: vendor

vendor: composer.json
	bin/composer install

venv:
	virtualenv venv
	venv/bin/pip install -r requirements.txt

docs: venv Resources/doc
	venv/bin/sphinx-build -E Resources/doc Resources/doc/_build
