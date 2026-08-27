#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#define DELIMITER "-----"
#define BUFFER_SIZE 4096
#define FAGI_CMD "EXEC AGI agi://127.0.0.1:4573/VDAD_ALL_outbound"
#define MOVED_MSG "ATTENTION! agi-VDAD_ALL_outbound.agi has replaced by a FastAGI at agi://127.0.0.1:4573/VDAD_ALL_outbound. You need to update your dialplan. Redirecting to the FastAGI.\n"

// Helper function to send commands to Asterisk and read the response
void send_agi_command(const char *cmd) {
	char response[BUFFER_SIZE];
	printf("%s\n", cmd);
	fflush(stdout);
	
	// Read response to keep communication synchronized
	if (fgets(response, sizeof(response), stdin) != NULL) {
		response[strcspn(response, "\r\n")] = 0;
	}
}

int main(int argc, char *argv[]) {
	char env_buffer[BUFFER_SIZE];

	// Read the environment block from Asterisk
	while (fgets(env_buffer, sizeof(env_buffer), stdin) != NULL) {
		if (strcmp(env_buffer, "\n") == 0 || strcmp(env_buffer, "\r\n") == 0) {
			break;
		}
	}

	// Try to let an Admin know of the changes
	fprintf(stderr, MOVED_MSG);

	// 2. Parse the argument string if it exists
	if (argc > 1 && argv != NULL && argv[1] != NULL) {
		char var_buf[BUFFER_SIZE] = "";
		char cmd_buf[BUFFER_SIZE];
		
		char *src = argv[1]; // Safely assigned now that we've checked argv[1] != NULL
		char *next_delim;
		size_t delim_len = strlen(DELIMITER);
		size_t var_len = 0;
		int need_comma = 0;

		// Find each occurrence of "-----"
		while ((next_delim = strstr(src, DELIMITER)) != NULL) {
			size_t segment_len = next_delim - src;

			if (segment_len > 0) {
				int written = snprintf(var_buf + var_len, sizeof(var_buf) - var_len, 
									   "%s%.*s", need_comma ? "," : "", (int)segment_len, src);
				
				if (written < 0 || (size_t)written >= sizeof(var_buf) - var_len) break;
				var_len += written;
				need_comma = 1;
			}
			src = next_delim + delim_len; 
		}

		// Append the final remaining part of the string if it contains data
		if (strlen(src) > 0 && var_len < sizeof(var_buf)) {
			snprintf(var_buf + var_len, sizeof(var_buf) - var_len, 
					 "%s%s", need_comma ? "," : "", src);
		}

		// ONLY send to Asterisk if we actually captured an argument string
		if (strlen(var_buf) > 0) {
			int cmd_written = snprintf(cmd_buf, sizeof(cmd_buf), "SET VARIABLE AGIARGS \"%s\"", var_buf);
			if (cmd_written >= 0 && (size_t)cmd_written < sizeof(cmd_buf)) {
				send_agi_command(cmd_buf);
			}
		}
	}

	// Hand off the call to the FastAGI server
	send_agi_command(FAGI_CMD);

	return 0;
}

