# Compiler settings
CC = gcc
CFLAGS = -O2 -Wall -Wextra -static

# Installation directory
AGI_BIN_DIR = /var/lib/asterisk/agi-bin

# Target binaries
TARGETS = agi-VDAD_ALL_outbound.agi agi-VDAD_ALL_inbound.agi VD_amd.agi

# Default rule (runs when you type 'make')
all: $(TARGETS)

# Compilation rules
agi-VDAD_ALL_outbound.agi: VDAD_ALL_outbound_redir.c
	$(CC) $(CFLAGS) -o $@ $<

agi-VDAD_ALL_inbound.agi: VDAD_ALL_inbound_redir.c
	$(CC) $(CFLAGS) -o $@ $<

VD_amd.agi: VD_amd_redir.c
	$(CC) $(CFLAGS) -o $@ $<

# Installation rule (runs when you type 'sudo make install')
install: all
	mkdir -p $(AGI_BIN_DIR)
	cp $(TARGETS) $(AGI_BIN_DIR)/
	chmod +x $(TARGETS:%=$(AGI_BIN_DIR)/%)
	@echo "AGI scripts installed successfully to $(AGI_BIN_DIR)"

# Cleanup rule (runs when you type 'make clean')
clean:
	rm -f $(TARGETS)

.PHONY: all install clean
