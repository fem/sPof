/**
 *
 */
function markup2html(text) {
  var newtext = text
    .replace(/\*\*(.+)\*\*/g, '<b>$1</b>')
    .replace(/\/\/(.+)\/\//g, '<i>$1</i>')
    .replace(/<del>(.+)<\/del>/g, '<strike>$1</strike>')
    .replace(/__(.+)__/g, '<u>$1</u>')
    .replace(/====([^=]*)====/g, '<h4>$1</h4>')
    .replace(/===([^=]*)===/g, '<h5>$1</h5>')
    .replace(/==([^=]*)==/g, '<h6>$1</h6>');
  var lines = newtext.split(/\n/g);
  var oldlevel = 0;
  var gentext = '';
  var listTypes = [];
  var first = true;

  $.each(lines, function(index, value) {
    var spaces = value.replace(/^([ ]*)(\*|-)(.*)$/, '$1');
    var listType = value.replace(/^([ ]*)(\*|-).*$/, '$2');
    var raw = value.replace(/^[ ]*(\*|-)(.*)$/, '$2');

    // which level are we on?
    if (spaces.length > 0 && ((spaces.length + 1) % 2) && (listType == '*' || listType == '-')) {
      level = spaces.length / 2;
    } else {
      level = 0;
    }

    // do nothing
    if (level == 0 && oldlevel == 0) {
      gentext += raw + '\n';
      return;
    }

    // open/close levels
    if (level > oldlevel) {
      listTypes[level] = listType;
      if (listType == '*') {
        gentext += '<ul>';
      } else {
        gentext += '<ol>';
      }
      first = true
    } else if (level < oldlevel) {
      for (var i=oldlevel; i > level; --i) {
        if (listTypes[i] == '*') {
          gentext += '</li></ul>\n';
        } else {
          gentext += '</li></ol>\n';
        }
        if (i > 0) {
          gentext += '</li>';
        }
      }
      first = true;
    }
    if (level > 0) {
      if (!first) {
        gentext += '</li>';
      }
      gentext += '<li>';
    }
    gentext += $.trim(raw);
    oldlevel = level;
    first = false;
  });

    if (oldlevel > 0) {
      for (var i=oldlevel; i > 0; --i) {
        if (listTypes[i] == '*') {
          gentext += '</li></ul>';
        } else {
          gentext += '</li></ol>';
        }
        if (i > 1) {
          gentext += '</li>';
        }
      }
    }
  return gentext;
}


/**
 *
 */
function html2markup(text) {
  var newtext = text
    .replace(/<b>(.*)<\/b>/g, '**$1**')
    .replace(/<i>(.*)<\/i>/g, '//$1//')
    .replace(/<strike>(.*)<\/strike>/g, '<del>$1</del>')
    .replace(/<u>(.*)<\/u>/g, '__$1__')
    .replace(/<h6>(.*)<\/h6>/g, '\n==$1==\n')
    .replace(/<h5>(.*)<\/h5>/g, '\n===$1===\n')
    .replace(/<h4>(.*)<\/h4>/g, '\n====$1====\n')
    .replace(/<span[^>]*>(.+)<\/span>/g, '$1')
    .replace(/<br[^>]*>/g, '\n');

  var lines = newtext.split(/</g);
  var gentext = '';
  var listType = [];
  var level = 0;

  $.each(lines, function(index, value) {
    if (value.match(/^ol>/)) {
      level++;
      listType[level] = '-';
      if (level == 1) {
        gentext += '\n';
      }
    } else if (value.match(/^\/ol>/)) {
      level--;
    } else if (value.match(/^ul>/)) {
      level++;
      listType[level] = '*';
      if (level == 1) {
        gentext += '\n';
      }
    } else if (value.match(/^\/ul>/)) {
      level--;
    } else if (value.match(/^li>/)) {
      gentext += new Array(level*2+1).join(' ') + listType[level] + value.replace(/^li>(.*)$/, '$1\n');
    } else if (value.match(/^\/li>/)) {
    } else {
      gentext += value;
    }
  });

  return gentext;
}


// define plugin
(function ( $ ) {

    var wysihtml5sPiRules = {
      tags: {
        strong: {
          "rename_tag": "b"
        },
        strike: {
          "rename_tag": "del"
        },
        em:     {
          "rename_tag": "i"
        },
        del:    {
        },
        b:      {
        },
        i:      {
        },
        u:      {
        },
        br:     {
        },
        ul:     {
        },
        ol:     {
        },
        li:     {
        },
        h1: {
            "rename_tag":"h4"
        },
        h2: {
            "rename_tag":"h5"
        },
        h3: {
            "rename_tag":"h6"
        },
        h4:     {
        },
        h5:     {
        },
        h6:     {
        }
      }
    };

    $.fn.spiwysihtml5 = function(toolbar) {

        $(this).uniqueId();
        var parent = $(this);

        // add dummy textarea where wysihtml5 can drop the html to
        var newtext = $('<textarea></textarea>');
        newtext.uniqueId();
        $(this).parent().append(newtext);

        newtext.val(markup2html($(this).val()));

        var dummytId = newtext.attr('id');

        // add wysiwyg editor to textarea
        new wysihtml5.Editor(dummytId, { // id of textarea element
            toolbar:      toolbar, // id of toolbar element
            parserRules: wysihtml5sPiRules
        });
        parent.css('display', 'none');

        // on submit convert to markup
        $(this).closest('form').submit(function() {
            console.log(newtext.val());
            parent.val(html2markup(newtext.val()));
        });

        return this;
    };


}( jQuery ));

$(function() {
    var header = $('<header class="wysihtml5-toolbar"></header>');
    var simple = $('<ul><li data-wysihtml5-command="bold"><strong>Fett</strong></li><li data-wysihtml5-command="italic"><i>Kursiv</i></li><li data-wysihtml5-command="strikethrough"><s>strikethrough</s></li><li data-wysihtml5-command="underline"><span style="text-decoration: underline;">Unterstrichen</span></li></ul>');
    var list = $('<ul><li data-wysihtml5-command="insertUnorderedList">Auflistung</li><li data-wysihtml5-command="insertOrderedList">Aufzählung</li></ul>');
    var headlines = $('<ul><li data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h4">H1</li><li data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h5">H2</li><li data-wysihtml5-command="formatBlock" data-wysihtml5-command-value="h6">H3</li></ul>');
    var help = $('<ul style="margin-left: 17px;"><li title="Die Buttons dienen zur Formatierung des Textes. Im Zweifelsfall einfach ausprobieren ;)"><a href="">?</a></li></ul>');

    header.uniqueId();
    header.append(simple);
    header.append(list);
    header.append(headlines);
    header.append(help);

    help.tooltip();
    header.insertBefore('.wysiwyg');

    // call plugin
    $('.wysiwyg').spiwysihtml5(header.attr('id'));
});

