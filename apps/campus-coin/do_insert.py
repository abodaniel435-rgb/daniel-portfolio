import re
src = open('core/views.py', encoding='utf-8').read()
# keep only first PulseView and first RoommateView blocks
def dedup(src, name):
    parts = src.split('class ' + name + '(APIView):')
    if len(parts) <= 2:
        return src
    head = parts[0]
    first = 'class ' + name + '(APIView):' + parts[1]
    rest = 'class ' + name + '(APIView):'.join(parts[2:])
    # cut second block: from its start to next class at same indent
    m = re.search(r'\nclass \w+', rest)
    tail = rest[m.start():] if m else ''
    return head + first + tail
src = dedup(src, 'PulseView')
src = dedup(src, 'RoommateView')
open('core/views.py', 'w', encoding='utf-8').write(src)
print('pulse', src.count('class PulseView'), 'mate', src.count('class RoommateView'))
import ast
ast.parse(src)
print('syntax OK')
