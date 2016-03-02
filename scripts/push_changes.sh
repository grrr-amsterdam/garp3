#!/usr/bin/env bash
if [ -d "garp" ]; then
	git subtree push -P garp git@github.com:grrr-amsterdam/garp3 develop
fi
