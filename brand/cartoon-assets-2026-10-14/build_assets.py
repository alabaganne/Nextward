from pathlib import Path
import subprocess, math, json, re
ROOT=Path(__file__).parent
C={'ink':'#392B31','hair':'#80553F','hairlight':'#A87554','white':'#FFFDF9','shade':'#EFE9E5','blush':'#F6B39C','main':'#2563EB','light':'#DBE7FF','dark':'#1D4ED8','mint':'#B9D0FF','yellow':'#F78C78'}
def path(d,fill='none',stroke=None,sw=7,extra=''):
 return f'<path d="{d}" fill="{C.get(fill,fill)}" stroke="{C.get(stroke or "ink",stroke or "ink")}" stroke-width="{sw}" stroke-linecap="round" stroke-linejoin="round" {extra}/>'
def rect(x,y,w,h,r,fill,sw=7):return f'<rect x="{x}" y="{y}" width="{w}" height="{h}" rx="{r}" fill="{C.get(fill,fill)}" stroke="{C["ink"]}" stroke-width="{sw}"/>'
def ellipse(x,y,rx,ry,fill,sw=0):return f'<ellipse cx="{x}" cy="{y}" rx="{rx}" ry="{ry}" fill="{C.get(fill,fill)}" stroke="{C["ink"]}" stroke-width="{sw}"/>'
def g(name,s,transform=''):return f'<g id="{name}"'+(f' transform="{transform}"' if transform else '')+'>'+s+'</g>'
def star(x,y,s=25,fill='yellow'):
 return path(f'M{x} {y-s} Q{x+4} {y-4} {x+s} {y} Q{x+4} {y+4} {x} {y+s} Q{x-4} {y+4} {x-s} {y} Q{x-4} {y-4} {x} {y-s} Z',fill,sw=4)
def lines(x,y,lengths,step=24,col='ink',sw=6):return ''.join(path(f'M{x} {y+i*step} h{z}',stroke=col,sw=sw) for i,z in enumerate(lengths))
def check(x,y,s=1):return path(f'M{x} {y} l{10*s} {11*s} l{23*s} {-27*s}',stroke='dark',sw=7*s)
def avatar(pose='wave',variant=0):
 # Original mascot: broad soft square head, asymmetric side-swept hair and pill-shaped limbs.
 a=''
 if pose=='wave':a+=g('backpack',rect(286,300,92,134,30,'main')+path('M310 322 v80',stroke='light',sw=8))
 a+=g('body',path('M203 302 C160 320 149 373 159 427 Q169 463 238 464 Q307 464 321 426 C334 376 312 328 280 308 Z','white')+path('M175 416 Q234 443 311 414 Q309 456 241 458 Q182 458 175 416','shade',sw=0))
 a+=g('feet',path('M173 421 C144 417 134 453 153 473 Q176 491 207 469 Q221 449 202 432 Z','white')+path('M279 429 C302 414 328 430 327 452 Q329 482 298 481 Q268 477 266 457 Z','white'))
 a+=g('neckwear',path('M187 309 Q240 334 293 307 L269 355 L235 341 L210 357 Z','main')+path('M267 330 Q300 326 309 347 Q293 365 268 347 Z','main'))
 if pose=='wave':a+=g('wave-arm',path('M174 345 Q144 359 120 332 L97 290 Q81 271 64 287 Q51 304 69 326 Q97 393 147 391 Q167 384 180 366 Z','white')+path('M64 287 Q62 269 72 266 Q86 262 96 285','white')+path('M45 248 l-10 -18 M70 238 l-2 -22 M99 244 l11 -19',stroke='main',sw=6))
 elif pose=='point':a+=g('point-arm',path('M299 342 Q332 360 355 333 L383 300 Q394 286 410 298 Q425 311 408 326 L383 355 Q344 404 304 383 Z','white')+path('M396 296 L413 280 Q426 270 434 280 Q439 291 425 303 L412 315','white'))
 else:a+=g('left-arm',path('M177 342 Q139 356 148 388 Q152 406 172 401 L205 378','white'))
 a+=g('head',path('M112 202 C105 129 150 85 234 80 C320 74 368 122 367 201 C368 270 325 310 242 315 C162 319 116 280 112 202 Z','white')+path('M122 235 Q146 293 238 294 Q325 290 357 238 Q347 307 243 310 Q140 311 122 235','shade',sw=0))
 if variant==2:
  hair='M114 188 Q92 146 121 108 L106 92 Q141 94 167 67 Q194 46 221 57 L237 42 Q270 57 286 69 Q344 63 363 112 Q385 135 367 181 Q348 166 335 129 Q282 156 235 122 Q182 155 140 141 Z'
 else:hair='M112 190 Q91 155 112 121 L98 107 Q129 107 150 81 Q174 55 209 64 L219 39 Q250 49 267 72 Q302 61 322 82 L344 72 Q341 92 360 110 Q382 145 365 183 Q343 172 329 133 Q302 150 270 139 Q288 125 289 114 Q248 149 210 142 Q222 129 226 115 Q189 148 146 143 Q127 159 112 190 Z'
 a+=g('hair',path(hair,'hair')+path('M143 115 Q169 81 201 84 M237 77 Q251 86 257 98',stroke='hairlight',sw=9))
 eyes=ellipse(193,213,11,18,'ink')+ellipse(286,213,11,18,'ink')+ellipse(190,207,4,6,'white')+ellipse(283,207,4,6,'white')
 if variant==2:eyes+=path('M166 199 Q192 187 218 201 v29 Q194 245 167 230 Z M261 201 Q288 187 314 199 v31 Q288 245 261 230 Z M219 210 h41',sw=5)
 a+=g('face',eyes+ellipse(164,244,15,8,'blush')+ellipse(317,244,15,8,'blush')+path('M217 248 Q239 271 262 248',sw=7))
 if pose=='wave':a+=g('rest-arm',path('M302 356 Q333 372 313 398 Q299 410 288 397 Q280 385 289 372','white'))
 if pose=='cv':
  a+=g('resume-held',rect(182,345,135,105,12,'light')+rect(198,360,102,71,5,'white',4)+ellipse(219,378,8,8,'main')+lines(239,373,[45,32],13,'main',4)+lines(211,405,[64,52],12,'ink',4))+g('hands',ellipse(177,386,19,24,'white',6)+ellipse(316,392,18,23,'white',6))
 if pose=='clip':
  a+=g('clipboard-held',rect(205,334,104,129,12,'main')+rect(217,347,80,103,5,'white',4)+rect(234,328,46,23,7,'yellow',4)+lines(234,375,[45,39,43],23,'ink',4)+check(237,433,.6))+g('hands',ellipse(201,389,18,24,'white',6)+ellipse(309,401,17,23,'white',6))
 if pose=='point':a+=g('rest-arm',path('M177 346 Q148 360 157 386 Q166 404 184 394 L195 377','white'))
 return a

def resume():return g('document',path('M128 72 H323 L398 147 V438 Q398 458 378 458 H128 Q108 458 108 438 V92 Q108 72 128 72 Z','white')+path('M323 72 V130 Q323 147 340 147 H398','light')+ellipse(175,170,32,32,'light')+ellipse(175,161,11,12,'main')+path('M154 191 Q158 172 176 174 Q193 174 197 191','main',sw=0)+lines(231,162,[102,72],26,'main',9)+lines(147,245,[199,169,193],30,'ink',7)+rect(147,357,91,58,12,'light',4)+rect(255,357,103,58,12,'mint',4)+check(275,389,1))
def board():return g('listing-board',rect(64,90,384,330,26,'main')+rect(82,113,348,287,16,'white',5)+path('M101 90 v-20 M412 90 v-20',sw=10)+rect(108,146,296,91,13,'light',4)+rect(126,166,50,50,11,'main',4)+path('M138 181 h26 v24 h-26 Z M144 181 v-8 h14 v8',stroke='white',sw=4)+lines(195,169,[169,132],22,'ink',6)+rect(196,209,82,11,5,'mint',0)+rect(108,260,296,110,13,'white',4)+ellipse(151,298,22,22,'yellow',4)+lines(195,284,[154,113],23,'ink',6)+rect(194,332,100,15,7,'light',0))
def search():return g('search-result',g('listing',board(),'translate(-5 12) scale(.82)')+g('magnifier',path('M337 322 L438 421 Q454 440 436 456 Q419 469 405 451 L305 346 Z','main')+ellipse(291,289,100,100,'white',8)+ellipse(291,289,78,78,'light',5)+lines(247,264,[90,72],27,'dark',8)+check(270,321,1.2)+path('M236 243 Q249 223 269 221',stroke='white',sw=10)))
def envelope():return g('application-envelope',path('M83 196 L248 78 Q259 70 272 79 L430 196 V416 Q430 433 412 433 H100 Q83 433 83 416 Z','main')+g('application',rect(139,111,235,246,15,'white')+rect(165,139,111,22,8,'light',0)+lines(166,189,[176,146,162],28,'ink',6)+ellipse(317,294,37,37,'mint',5)+check(300,297,1))+path('M83 196 L256 319 L430 196 V416 Q430 433 411 433 H102 Q83 433 83 416 Z','light')+path('M89 421 L209 307 M424 421 L304 307',sw=6)+star(420,100,28))
def calendar():return g('interview-calendar',rect(77,104,357,328,27,'white')+path('M104 104 H407 Q434 104 434 132 V191 H77 V132 Q77 104 104 104 Z','main')+path('M153 77 v54 M357 77 v54',stroke='ink',sw=15)+path('M153 77 v47 M357 77 v47',stroke='light',sw=7)+''.join(ellipse(x,y,6,6,'shade') for x in [127,190,253,316,379] for y in [232,284,336,388])+rect(223,252,65,63,12,'mint',4)+check(239,283,.9)+ellipse(383,389,72,72,'yellow',6)+path('M383 348 v41 l26 15',sw=7))
def laptop():return g('portfolio-laptop',rect(91,91,331,260,21,'main')+rect(107,110,299,224,10,'white',4)+rect(124,127,265,30,8,'light',0)+''.join(ellipse(139+i*14,142,3,3,'main') for i in range(3))+rect(127,175,108,137,10,'light',4)+ellipse(180,216,23,23,'main')+path('M143 276 Q146 245 181 245 Q214 245 220 276','main',sw=0)+lines(253,187,[111,87],19,'dark',5)+rect(252,235,112,61,8,'mint',4)+path('M268 277 l24 -23 l18 16 l18 -13 l21 20',sw=4)+path('M91 351 L49 398 Q39 418 62 424 H449 Q471 419 462 399 L422 351 Z','shade')+path('M208 352 l-13 28 h127 l-15 -28','light',sw=4)+path('M81 405 h350',stroke='white',sw=7))
def learning():return g('learning-stack',rect(97,325,317,84,16,'main')+path('M123 345 H413 V390 H123 Q103 368 123 345 Z','white',sw=5)+lines(141,360,[247,247],14,'shade',4)+rect(71,251,311,73,14,'mint')+path('M97 268 H381 V307 H97 Q80 287 97 268 Z','white',sw=5)+lines(113,281,[243,243],12,'shade',4)+path('M292 269 v71 l-18 -13 l-19 13 v-71','yellow',sw=4)+path('M151 161 v67 Q253 281 355 228 v-67','main')+path('M63 152 L253 76 L444 152 L253 224 Z','dark')+path('M253 150 l155 25 v83',stroke='yellow',sw=6)+ellipse(409,269,12,20,'yellow',4)+path('M133 151 l119 -46 l77 29',stroke='main',sw=6))
def wand():return g('wand-and-stars',path('M119 399 L323 190 Q335 176 350 192 Q364 204 351 218 L151 427 Q135 443 121 428 Q108 416 119 399 Z','main')+path('M299 215 l28 28',stroke='light',sw=7)+path('M348 74 L368 127 L425 132 L382 170 L394 225 L346 196 L298 226 L311 172 L268 135 L325 128 Z','yellow')+path('M344 105 l-11 39 l-32 4',stroke='white',sw=6)+star(150,169,33,'mint')+star(411,324,30,'light')+star(228,82,17,'light')+path('M143 296 Q111 253 132 221 M340 336 Q367 314 366 274',stroke='main',sw=6)+ellipse(218,356,7,7,'yellow')+ellipse(405,81,7,7,'mint'))
PROPS=[resume,board,search,envelope,calendar,laptop,learning,wand]
NAMES=['student-wave','student-prepare-cv','recruiter-clipboard','mentor-pointing','resume-document','internship-listing-board','search-internships','application-envelope','interview-calendar','portfolio-laptop','learning-books-and-cap','wand-and-stars','discover-internship','prepare-application','student-company-conversation','first-day-celebration']
def scene(i):
 halo=g('scene-accent',ellipse(550,391,441,257,'light')+ellipse(874,178,41,41,'mint')+star(175,150,28,'yellow'))
 if i==0:
  return halo+g('opportunities',board(),'translate(485 90) scale(1.04)')+g('student',avatar('point'),'translate(85 145) scale(1.03)')+g('search-symbol',search(),'translate(725 365) scale(.48)')+g('discovery-spark',star(784,113,28)+star(935,358,18,'mint'))
 if i==1:
  return halo+g('work-desk',rect(166,522,769,33,14,'hair')+path('M200 554 v100 M897 554 v100',sw=16))+g('student',avatar('cv'),'translate(107 77) scale(1.02)')+g('portfolio',laptop(),'translate(469 221) scale(.86)')+g('application',envelope(),'translate(768 99) scale(.46)')+g('skills',learning(),'translate(56 385) scale(.47)')+g('ready-spark',star(698,141,25,'mint'))
 if i==2:
  return halo+g('student',avatar('wave'),'translate(50 156) scale(.99)')+g('recruiter',avatar('clip',2),'translate(570 156) scale(.99)')+g('student-speech',path('M329 90 H540 Q563 90 563 113 V198 Q563 221 540 221 H402 L366 250 L371 221 H329 Q307 221 307 198 V113 Q307 90 329 90 Z','white')+lines(341,125,[188,152],29,'main',9)+star(526,186,16,'mint'))+g('recruiter-speech',path('M614 93 H784 Q807 93 807 116 V178 Q807 201 784 201 H758 L774 228 L733 201 H614 Q591 201 591 178 V116 Q591 93 614 93 Z','mint')+check(663,154,1.7))
 return halo+g('welcome-door',rect(626,150,274,439,26,'main')+rect(646,170,235,419,15,'white',5)+rect(683,212,157,71,12,'light',4)+path('M718 247 l15 14 l35 -32',stroke='main',sw=7)+ellipse(848,395,8,8,'yellow',4))+g('student',avatar('wave'),'translate(140 158) scale(1.02)')+g('welcome-kit',rect(501,445,132,120,12,'yellow')+path('M501 479 h132 M567 445 v120',stroke='main',sw=10)+path('M563 442 Q522 443 528 420 Q539 400 563 442 Q585 400 602 417 Q616 442 570 442','mint',sw=5))+g('confetti',star(514,132,33)+star(928,311,23,'mint')+''.join(path(d,stroke=c,sw=10) for d,c in [('M220 119 l-13 -24','main'),('M379 111 l15 -21','mint'),('M921 144 l18 -20','main'),('M111 336 l-21 9','yellow'),('M558 291 l21 8','main')]))
def svg(body,name,w=768,h=768):return f'''<svg xmlns="http://www.w3.org/2000/svg" width="{w}" height="{h}" viewBox="0 0 {w} {h}" role="img" aria-labelledby="title desc"><title id="title">Nextward · {name.replace('-',' ')}</title><desc id="desc">Original editable cartoon illustration for internship onboarding and explainer videos. Transparent background.</desc>{body}</svg>'''
def main():
 for i,name in enumerate(NAMES):
  if i<4:body=g('character',avatar(['wave','cv','clip','point'][i],2 if i==2 else 0),'translate(44 24) scale(1.42)');w=h=768
  elif i<12:body=g('prop',PROPS[i-4](),'translate(24 24) scale(1.4)');w=h=768
  else:body=scene(i-12);w=1100;h=760
  file=ROOT/'svg'/f'{i+1:02d}-{name}.svg'
  ids={}
  def unique_id(m):
   key=m.group(1);ids[key]=ids.get(key,0)+1
   return 'id="'+key+(f'-{ids[key]}' if ids[key]>1 else '')+'"'
  file.write_text(re.sub(r'id="([^"]+)"',unique_id,svg(body,name,w,h)))
  subprocess.run(['inkscape',str(file),'--export-type=png','--export-width=1600',f'--export-filename={ROOT/"png"/(file.stem+".png")}'],check=True,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
 (ROOT/'palette.json').write_text(json.dumps(C,indent=2))
if __name__=='__main__':main()
