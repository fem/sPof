#!/bin/bash

if [ "$(basename $(pwd))" = "bin" ]; then
  BASE=$(dirname $(pwd))
else
  BASE=`pwd`
fi;

$BASE/vendor/bin/tsmarty2c.php -o smarty.pot $BASE/template/html
find $BASE/source -iname "*.php" \
| xargs xgettext --add-comments=TRANSLATORS: --from-code=UTF-8 --keyword=gettext --keyword=dgettext --keyword=__ --keyword=_s --keyword=_ --output=code.pot

msgcat -o spof.pot code.pot smarty.pot
rm -f code.pot smarty.pot
