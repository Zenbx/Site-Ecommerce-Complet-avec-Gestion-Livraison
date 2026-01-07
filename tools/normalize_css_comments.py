"""Script pour normaliser les commentaires dans tous les fichiers CSS sous resources/css.
Règles appliquées :
- Chaque commentaire /* ... */ devient sur une seule ligne si court, sinon garde plusieurs lignes.
- Supprime espaces multiples.
- Met une majuscule initiale.
- Ajoute un point final si le commentaire ressemble à une phrase et n'en a pas.
- Ne change pas le sens du texte.

Usage: python3 tools/normalize_css_comments.py
"""
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / 'techstorm-client' / 'resources' / 'css'

COMMENT_RE = re.compile(r'/\*([\s\S]*?)\*/', re.MULTILINE)


def normalize_text(s: str) -> str:
    # collapse multiple spaces and trim
    s = re.sub(r'\s+', ' ', s.strip())
    if not s:
        return s
    # ensure single space after punctuation where needed
    s = re.sub(r'\s*([.,;:!?])\s*', r'\1 ', s)
    s = s.strip()
    # capitalize first letter
    if len(s) > 0:
        s = s[0].upper() + s[1:]
    # add trailing period if it looks like a sentence (has spaces and no end punctuation)
    if ' ' in s and s[-1] not in '.!?':
        s = s + '.'
    return s


def normalize_comment(match: re.Match) -> str:
    inner = match.group(1)
    # if multiline, preserve line breaks but normalize each line
    lines = inner.splitlines()
    if len(lines) == 1:
        txt = normalize_text(lines[0])
        return f'/* {txt} */' if txt else '/* */'
    # for multiple lines, normalize each line and join with newline and a single leading space
    norm_lines = []
    for line in lines:
        norm_lines.append(' ' + normalize_text(line) if line.strip() else '')
    joined = '\n'.join(l.rstrip() for l in norm_lines)
    return f'/*{joined}\n */'


def process_file(path: Path) -> bool:
    text = path.read_text(encoding='utf-8')
    new_text, count = COMMENT_RE.subn(normalize_comment, text)
    if count > 0 and new_text != text:
        path.write_text(new_text, encoding='utf-8')
        print(f'Updated {path} ({count} comments)')
        return True
    return False


def main():
    if not ROOT.exists():
        print('CSS directory not found:', ROOT)
        return
    files = list(ROOT.rglob('*.css'))
    updated = []
    for f in files:
        try:
            if process_file(f):
                updated.append(f)
        except Exception as e:
            print('Error processing', f, e)
    print('\nDone. Files updated:', len(updated))


if __name__ == '__main__':
    main()
