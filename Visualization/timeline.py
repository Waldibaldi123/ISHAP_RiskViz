import matplotlib

#Ubuntu specific
matplotlib.use('Agg')

import matplotlib.patches as mpatches
import matplotlib.dates as mdates
from matplotlib import pyplot as plt 

from datetime import datetime
from datetime import timedelta

import numpy as np 
import json
import sys

#############functions################
######################################

def formatToDatetime(companyDict):
	for s in companyDict:
		#special case key: 'company' because it is no array
		if s == 'company':
			if companyDict[s]['endDate'] != 'today':
				companyDict[s]['endDate'] = datetime.strptime(companyDict[s]['endDate'], "%Y-%m-%d")
			companyDict[s]['scheinunternehmenDate'] = datetime.strptime(companyDict[s]['scheinunternehmenDate'], "%Y-%m-%d")
		else:
			for i in range(0, len(companyDict[s])):
				for k,v in companyDict[s][i].items():
					try:
						companyDict[s][i][k] = datetime.strptime(v, "%Y-%m-%d")
					except:
						if companyDict[s][i][k] == 'today':
							companyDict[s][i][k] = datetime.now()
						continue
	return companyDict

def convertToDays(companyDict):
	for k in companyDict:
		if k != 'company':
			for i in range(0, len(companyDict[k])):
				companyDict[k][i]['days'] = (companyDict[k][i].pop('dateTo') - companyDict[k][i]['dateFrom']).days
	return companyDict

def parseJson(companyName):
	with open('jsonFiles/' + companyName, 'r') as file:
		manz = json.loads(file.read())
	# with open('jsonFiles/' + companyName + 'GISA.json', 'r') as file:
	# 	gisa = json.loads(file.read())

	#merge the dictionaries 
	# companyDict = {**manz, **gisa}

	companyDict = manz

	#we need to parse dates to datetime format
	companyDict = formatToDatetime(companyDict)

	#instead of dateTo we need days to graph the company
	companyDict = convertToDays(companyDict)

	return companyDict

def createPersonDict(companyDict):
	personDict = {}
	topLevel = 0
	bottomLevel = 0

	colorList = ['red', 'blue', 'green', 'orange', 'purple', 'chartreuse', 'saddlebrown', 'teal', 'darkslateblue','olive']
	colorNr = -1

	for k in companyDict:
		if k == 'companyLeaders' or k == 'companyOwners' or k == 'gewerbeFuehrer':
			for obj in companyDict[k]:
				text = obj['text']

				if text not in personDict:
					personDict[text] = {}

				if k == 'companyLeaders':
					if 'topLevel' not in personDict[text]:
						topLevel += 1
						personDict[text]['topLevel'] = topLevel

				if k == 'companyOwners':
					if 'bottomLevel' not in personDict[text]:
						bottomLevel -= 1
						personDict[text]['bottomLevel'] = bottomLevel

				if k == 'gewerbeFuehrer':
					if 'gewerbeFuehrer' not in personDict[text]:
						topLevel += 1
						personDict[text]['gewerbeFuehrer'] = topLevel + 1

				if 'color' not in personDict[text]:
					colorNr += 1
					personDict[text]['color'] = colorList[colorNr]

	return personDict

def getAlpha(capital, stammeinlage):
	if capital == None:
		return 1.0
	else:
		return float(capital) / float(stammeinlage)

def getCapitalPercent(capital, stammeinlage):
	if capital == None:
		return ''
	else:
		return ' (' + str((float(capital) / float(stammeinlage)) * 100) + '%)'

def getPatches(personDict):
	boxPatches = []
	boxPatches.append(mpatches.Patch(color='yellow', label='Addresswechsel'))
	boxPatches.append(mpatches.Patch(color='cyan', label='Namenswechsel'))
	boxPatches.append(mpatches.Patch(color='gray', label='BMF: Scheinunternehmen'))
	boxPatches.append(mpatches.Patch(color='black', label='Konkurs/Insolvenz/Loschung'))

	personPatches = []
	for k in personDict:
		personPatches.append(mpatches.Patch(color=personDict[k]['color'], label=k))

	return {'boxPatches': boxPatches, 'personPatches': personPatches}

def drawGraph(companyDict, personDict, firstArg):
	f, ax = plt.subplots(figsize=(20, 7.5))

	#helper to draw spans to the end of the last bar
	endOfLastBarh = companyDict['companyNames'][0]['dateFrom']
	for k in companyDict:
		if k == 'company':
			continue
		for obj in companyDict[k]:
			if k == 'companyLeaders' or k == 'companyOwners' or k == 'gewerbeFuehrer':
				if obj['dateFrom']+timedelta(days=obj['days']) > endOfLastBarh:
					endOfLastBarh = obj['dateFrom']+timedelta(days=obj['days'])

	#helper to draw divider between gewerbe and the rest
	dividerDrawn = False

	for k in companyDict:
		if k == 'company':
			stammeinlage = companyDict[k]['stammeinlage']
			continue
		for obj in companyDict[k]:
			if 'text' in obj:
				text = obj['text']

			if k == 'companyLeaders' and 'topLevel' in personDict[text]:
				ax.barh(personDict[text]['topLevel'], obj['days'], left=obj['dateFrom'], color=personDict[text]['color'])
				ax.annotate(str(obj['days']) + " Tage", xy=(obj['dateFrom'] + timedelta(days=obj['days']/2), personDict[text]['topLevel']), ha='center', va='center')
			
			if k == 'companyOwners' and 'bottomLevel' in personDict[text]:
				ax.barh(personDict[text]['bottomLevel'], obj['days'], left=obj['dateFrom'], color=personDict[text]['color'], alpha=getAlpha(obj['capital'], stammeinlage))
				ax.annotate(str(obj['days']) + " Tage" + getCapitalPercent(obj['capital'], stammeinlage), xy=(obj['dateFrom'] + timedelta(days=obj['days']/2), personDict[text]['bottomLevel']), ha='center', va='center')
				
			if k == 'gewerbeFuehrer' and 'gewerbeFuehrer' in personDict[text]:
				if not dividerDrawn:
					plt.axhline(y=personDict[text]['gewerbeFuehrer'] - 1, color='black', linestyle='--')
					dividerDrawn = True
				ax.barh(personDict[text]['gewerbeFuehrer'], obj['days'], left=obj['dateFrom'], color='white', edgecolor=personDict[text]['color'], linewidth=4)
				
			if k == 'companyNames':
				ax.text(obj['dateFrom'], 0.1, '  ', bbox=dict(facecolor='cyan', edgecolor='black'))

			if k == 'companyAddresses':
				ax.text(obj['dateFrom'], -0.1, '  ', bbox=dict(facecolor='yellow', edgecolor='black'))

	ax.xaxis_date()
	ax.get_xaxis().set_major_locator(mdates.MonthLocator(interval=6))
	ax.get_xaxis().set_major_formatter(mdates.DateFormatter("%b %Y"))
	# ax.set_ylabel("Gesellschafter     Geschaftsfuhrer", fontsize=16)
	plt.setp(ax.get_ymajorticklabels(), visible=False)
	ax.set_title(companyDict['companyNames'][len(companyDict['companyNames'])-1]['text'], fontsize=20)
	
	plt.axhline(y=0, color='black', linestyle='-')
	ax.text(companyDict['company']['scheinunternehmenDate'], 0, '  ', bbox=dict(facecolor='gray', edgecolor='black'))
	if companyDict['company']['endDate'] != 'today':
		plt.axvline(x=companyDict['company']['endDate'], color='black', linestyle='-', linewidth=6)
		plt.axvspan(companyDict['company']['scheinunternehmenDate'], companyDict['company']['endDate'], facecolor='none', hatch='X', edgecolor='white')
	else:
		plt.axvspan(companyDict['company']['scheinunternehmenDate'], endOfLastBarh, facecolor='none', hatch='X', edgecolor='white')

	patches = getPatches(personDict)
	legend1 = plt.legend(handles=patches['personPatches'], loc='upper center', bbox_to_anchor=(0.5, -0.05), fancybox=True, shadow=True, ncol=100)
	legend2 = plt.legend(handles=patches['boxPatches'], loc='lower left')
	plt.gca().add_artist(legend1)
	plt.gca().add_artist(legend2)

	plt.savefig("images/" + firstArg + ".png", bbox_inches='tight', pad_inches=0.4)

def main():
	if len(sys.argv) != 2:
		print("ERROR: Wrong number of args")
		print('Usage: python3 timeline "companyName"')
		return

	#parse Json to usable format
	companyDict = parseJson(sys.argv[1])

	#create person Dictionary
	personDict = createPersonDict(companyDict)

	drawGraph(companyDict, personDict, sys.argv[1])


if __name__=="__main__":
	main()