#!/usr/bin/python

import os
from bs4 import BeautifulSoup

#pip install beautifulsoup4

#DIR_CURR='/home/ec2-user/iconic-scraping'
DIR_CURR=os.getcwd()
DIR_DATA=DIR_CURR+'/svg'

results = []
for fname in os.listdir(DIR_DATA):
  f = open(DIR_DATA+'/'+fname)
  data = f.read()
  f.close()
  # scrape by beautiful soup
  soup = BeautifulSoup(data, "html.parser")
  d    = soup.find('path').get('d') if soup and soup.find('path') else ''
  if d != '':
    results.append([fname.replace('.svg',''), d])
  #print data

fw = open(DIR_CURR+'/icons.csv', 'w')
fw.writelines("name,dval\n")
for row in results:
  fw.writelines(row[0] + ',' + row[1]+"\n")
  #print row[0]
  #print row[1]
fw.close()
  
